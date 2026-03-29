<?php

declare(strict_types=1);

namespace CA\Crl\Services;

use CA\Crl\Contracts\CrlDistributionInterface;
use CA\Crl\Contracts\CrlManagerInterface;
use CA\Crl\Models\Crl;
use CA\Crt\Models\Certificate;
use CA\Models\CertificateStatus;
use CA\Crl\Events\CrlGenerated;
use CA\Exceptions\CrlException;
use CA\Key\Contracts\KeyManagerInterface;
use CA\Models\CertificateAuthority;
use Illuminate\Support\Collection;

class CrlManager implements CrlManagerInterface
{
    public function __construct(
        private readonly CrlGenerator $generator,
        private readonly KeyManagerInterface $keyManager,
        private readonly CrlDistributionInterface $distributor,
    ) {}

    public function generate(CertificateAuthority $ca): Crl
    {
        $lifetimeHours = (int) config('ca-crl.lifetime_hours', 24);

        // Gather all revoked certificates for this CA
        $revokedCerts = $this->getRevokedCertificates($ca);

        // Determine the next CRL number
        $crlNumber = $this->getNextCrlNumber($ca);

        // Load the CA's private key
        $signingKey = $this->loadSigningKey($ca);

        // Load the CA's certificate PEM
        $caCertPem = $this->loadCaCertificatePem($ca);

        // Build the CRL
        $result = $this->generator->buildCrl(
            ca: $ca,
            revokedCerts: $revokedCerts,
            crlNumber: $crlNumber,
            signingKey: $signingKey,
            caCertificatePem: $caCertPem,
            lifetimeHours: $lifetimeHours,
        );

        $now = now();

        // Store the CRL in the database
        $crl = Crl::create([
            'ca_id' => $ca->getId(),
            'tenant_id' => $ca->getTenantId(),
            'crl_number' => $crlNumber,
            'this_update' => $now,
            'next_update' => $now->copy()->addHours($lifetimeHours),
            'crl_pem' => $result['pem'],
            'crl_der' => $result['der'],
            'signature_algorithm' => $result['signature_algorithm'],
            'entries_count' => count($revokedCerts),
            'is_delta' => false,
            'base_crl_number' => null,
            'storage_path' => null,
        ]);

        // Fire the event
        CrlGenerated::dispatch($crl);

        return $crl;
    }

    public function generateDelta(CertificateAuthority $ca, Crl $baseCrl): Crl
    {
        $lifetimeHours = (int) config('ca-crl.lifetime_hours', 24);

        // Gather only certificates revoked since the base CRL's this_update
        $revokedCerts = $this->getRevokedCertificatesSince($ca, $baseCrl->this_update);

        $crlNumber = $this->getNextCrlNumber($ca);
        $signingKey = $this->loadSigningKey($ca);
        $caCertPem = $this->loadCaCertificatePem($ca);

        $result = $this->generator->buildDeltaCrl(
            ca: $ca,
            revokedCerts: $revokedCerts,
            crlNumber: $crlNumber,
            baseCrlNumber: (int) $baseCrl->crl_number,
            signingKey: $signingKey,
            caCertificatePem: $caCertPem,
            lifetimeHours: $lifetimeHours,
        );

        $now = now();

        $crl = Crl::create([
            'ca_id' => $ca->getId(),
            'tenant_id' => $ca->getTenantId(),
            'crl_number' => $crlNumber,
            'this_update' => $now,
            'next_update' => $now->copy()->addHours($lifetimeHours),
            'crl_pem' => $result['pem'],
            'crl_der' => $result['der'],
            'signature_algorithm' => $result['signature_algorithm'],
            'entries_count' => count($revokedCerts),
            'is_delta' => true,
            'base_crl_number' => $baseCrl->crl_number,
            'storage_path' => null,
        ]);

        CrlGenerated::dispatch($crl);

        return $crl;
    }

    public function getCurrent(CertificateAuthority $ca): ?Crl
    {
        return Crl::query()
            ->forCa($ca->getId())
            ->current()
            ->first();
    }

    public function isRevoked(CertificateAuthority $ca, string $serial): bool
    {
        return Certificate::query()
            ->where('ca_id', $ca->getId())
            ->where('serial_number', $serial)
            ->where('status', CertificateStatus::REVOKED)
            ->exists();
    }

    public function publish(Crl $crl): void
    {
        $this->distributor->publish($crl);
    }

    public function getAll(CertificateAuthority $ca): Collection
    {
        return Crl::query()
            ->forCa($ca->getId())
            ->orderByDesc('crl_number')
            ->get();
    }

    public function findByUuid(string $uuid): ?Crl
    {
        return Crl::query()->where('uuid', $uuid)->first();
    }

    /**
     * Get all revoked certificates for a CA as an array suitable for CRL generation.
     *
     * @return array<int, array{serial: string, date: string, reason: int}>
     */
    private function getRevokedCertificates(CertificateAuthority $ca): array
    {
        return Certificate::query()
            ->where('ca_id', $ca->getId())
            ->where('status', CertificateStatus::REVOKED)
            ->whereNotNull('revoked_at')
            ->get()
            ->map(fn (Certificate $cert): array => [
                'serial' => $cert->serial_number,
                'date' => $cert->revoked_at->format('Y-m-d H:i:s'),
                'reason' => $cert->revocation_reason ?? 0,
            ])
            ->values()
            ->all();
    }

    /**
     * Get certificates revoked since a given date.
     *
     * @return array<int, array{serial: string, date: string, reason: int}>
     */
    private function getRevokedCertificatesSince(CertificateAuthority $ca, \DateTimeInterface $since): array
    {
        return Certificate::query()
            ->where('ca_id', $ca->getId())
            ->where('status', CertificateStatus::REVOKED)
            ->whereNotNull('revoked_at')
            ->where('revoked_at', '>', $since)
            ->get()
            ->map(fn (Certificate $cert): array => [
                'serial' => $cert->serial_number,
                'date' => $cert->revoked_at->format('Y-m-d H:i:s'),
                'reason' => $cert->revocation_reason ?? 0,
            ])
            ->values()
            ->all();
    }

    /**
     * Determine the next CRL number for a CA.
     */
    private function getNextCrlNumber(CertificateAuthority $ca): int
    {
        $latest = Crl::query()
            ->where('ca_id', $ca->getId())
            ->max('crl_number');

        return $latest !== null ? ((int) $latest + 1) : 1;
    }

    /**
     * Load the CA's signing private key via the KeyManager.
     *
     * @throws CrlException
     */
    private function loadSigningKey(CertificateAuthority $ca): \phpseclib3\Crypt\Common\PrivateKey
    {
        try {
            $key = $ca->key ?? $ca->keys()->latest()->first();

            if ($key === null) {
                throw new CrlException("No signing key found for CA [{$ca->getId()}].");
            }

            return $this->keyManager->decryptPrivateKey($key);
        } catch (CrlException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new CrlException(
                "Failed to load signing key for CA [{$ca->getId()}]: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Load the CA's certificate in PEM format.
     *
     * @throws CrlException
     */
    private function loadCaCertificatePem(CertificateAuthority $ca): string
    {
        $certificate = Certificate::query()
            ->where('ca_id', $ca->getId())
            ->whereNotNull('certificate_pem')
            ->first();

        if ($certificate === null || empty($certificate->certificate_pem)) {
            throw new CrlException("No certificate found for CA [{$ca->getId()}].");
        }

        return $certificate->certificate_pem;
    }
}
