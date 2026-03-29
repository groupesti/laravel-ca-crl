<?php

declare(strict_types=1);

namespace CA\Crl\Services;

use CA\Exceptions\CrlException;
use CA\Models\CertificateAuthority;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\File\X509;

class CrlGenerator
{
    /**
     * Build a signed CRL using phpseclib v3.
     *
     * @param  CertificateAuthority  $ca
     * @param  array<int, array{serial: string, date: string, reason: int}>  $revokedCerts
     * @param  int  $crlNumber
     * @param  PrivateKey  $signingKey
     * @param  string  $caCertificatePem
     * @param  int  $lifetimeHours
     * @return array{pem: string, der: string, signature_algorithm: string}
     *
     * @throws CrlException
     */
    public function buildCrl(
        CertificateAuthority $ca,
        array $revokedCerts,
        int $crlNumber,
        PrivateKey $signingKey,
        string $caCertificatePem,
        int $lifetimeHours = 24,
    ): array {
        try {
            // Load the CA certificate to use as the issuer
            $issuerCert = new X509();
            $issuerCert->loadX509($caCertificatePem);

            // Create the CRL object
            $crl = new X509();
            $crl->loadCRL($crl->saveCRL($crl->signCRL($issuerCert, $crl, 'sha256WithRSAEncryption')));

            // Build a fresh CRL
            $crlBuilder = new X509();
            $crlBuilder->setSerialNumber($crlNumber, 10);

            // Set the start date and end date for the CRL
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $crlBuilder->setStartDate($now);
            $crlBuilder->setEndDate($now->modify("+{$lifetimeHours} hours"));

            // Revoke each certificate
            foreach ($revokedCerts as $entry) {
                $serial = $entry['serial'];
                $revokedDate = $entry['date'];
                $reason = $entry['reason'] ?? 0;

                $crlBuilder->setRevokedCertificateExtension($serial, 'id-ce-cRLReasons', $this->mapReasonToString($reason));
            }

            // Revoke the certs (add them to the CRL)
            foreach ($revokedCerts as $entry) {
                $crlBuilder->revoke($entry['serial'], $entry['date']);
            }

            // Set CRL Number extension
            $crlBuilder->setExtension('id-ce-cRLNumber', $crlNumber);

            // Sign the CRL with the CA's private key
            $signedCrl = $crlBuilder->signCRL($issuerCert, $crlBuilder, 'sha256WithRSAEncryption');

            // Save in DER and PEM formats
            $derBytes = $crlBuilder->saveCRL($signedCrl);
            $pemString = $crlBuilder->saveCRL($signedCrl, X509::FORMAT_PEM);

            // Determine the signature algorithm from the signed CRL
            $signatureAlgorithm = $this->extractSignatureAlgorithm($ca);

            return [
                'pem' => $pemString,
                'der' => $derBytes,
                'signature_algorithm' => $signatureAlgorithm,
            ];
        } catch (CrlException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new CrlException(
                "Failed to build CRL for CA [{$ca->getId()}]: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Build a delta CRL containing only certificates revoked since the base CRL.
     *
     * @param  CertificateAuthority  $ca
     * @param  array<int, array{serial: string, date: string, reason: int}>  $revokedCerts
     * @param  int  $crlNumber
     * @param  int  $baseCrlNumber
     * @param  PrivateKey  $signingKey
     * @param  string  $caCertificatePem
     * @param  int  $lifetimeHours
     * @return array{pem: string, der: string, signature_algorithm: string}
     *
     * @throws CrlException
     */
    public function buildDeltaCrl(
        CertificateAuthority $ca,
        array $revokedCerts,
        int $crlNumber,
        int $baseCrlNumber,
        PrivateKey $signingKey,
        string $caCertificatePem,
        int $lifetimeHours = 24,
    ): array {
        try {
            $issuerCert = new X509();
            $issuerCert->loadX509($caCertificatePem);

            $crlBuilder = new X509();
            $crlBuilder->setSerialNumber($crlNumber, 10);

            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $crlBuilder->setStartDate($now);
            $crlBuilder->setEndDate($now->modify("+{$lifetimeHours} hours"));

            // Revoke each certificate
            foreach ($revokedCerts as $entry) {
                $crlBuilder->revoke($entry['serial'], $entry['date']);
                $crlBuilder->setRevokedCertificateExtension(
                    $entry['serial'],
                    'id-ce-cRLReasons',
                    $this->mapReasonToString($entry['reason'] ?? 0),
                );
            }

            // Set CRL Number extension
            $crlBuilder->setExtension('id-ce-cRLNumber', $crlNumber);

            // Set Delta CRL Indicator extension (base CRL number)
            $crlBuilder->setExtension('id-ce-deltaCRLIndicator', $baseCrlNumber, true);

            $signedCrl = $crlBuilder->signCRL($issuerCert, $crlBuilder, 'sha256WithRSAEncryption');

            $derBytes = $crlBuilder->saveCRL($signedCrl);
            $pemString = $crlBuilder->saveCRL($signedCrl, X509::FORMAT_PEM);

            $signatureAlgorithm = $this->extractSignatureAlgorithm($ca);

            return [
                'pem' => $pemString,
                'der' => $derBytes,
                'signature_algorithm' => $signatureAlgorithm,
            ];
        } catch (CrlException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new CrlException(
                "Failed to build delta CRL for CA [{$ca->getId()}]: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Map a CRL reason code (int) to the phpseclib reason string.
     */
    private function mapReasonToString(int $reason): string
    {
        return match ($reason) {
            0 => 'unspecified',
            1 => 'keyCompromise',
            2 => 'cACompromise',
            3 => 'affiliationChanged',
            4 => 'superseded',
            5 => 'cessationOfOperation',
            6 => 'certificateHold',
            8 => 'removeFromCRL',
            9 => 'privilegeWithdrawn',
            10 => 'aACompromise',
            default => 'unspecified',
        };
    }

    /**
     * Determine the signature algorithm string from the CA's hash algorithm.
     */
    private function extractSignatureAlgorithm(CertificateAuthority $ca): string
    {
        $hash = $ca->hash_algorithm ?? 'sha256';

        return $hash . 'WithRSAEncryption';
    }
}
