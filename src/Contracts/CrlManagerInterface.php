<?php

declare(strict_types=1);

namespace CA\Crl\Contracts;

use CA\Crl\Models\Crl;
use CA\Models\CertificateAuthority;
use Illuminate\Support\Collection;

interface CrlManagerInterface
{
    /**
     * Generate a full CRL for the given CA.
     */
    public function generate(CertificateAuthority $ca): Crl;

    /**
     * Generate a delta CRL based on a full (base) CRL.
     */
    public function generateDelta(CertificateAuthority $ca, Crl $baseCrl): Crl;

    /**
     * Get the current (latest non-expired) CRL for a CA.
     */
    public function getCurrent(CertificateAuthority $ca): ?Crl;

    /**
     * Check if a certificate serial number is revoked in the current CRL.
     */
    public function isRevoked(CertificateAuthority $ca, string $serial): bool;

    /**
     * Publish a CRL to the configured storage.
     */
    public function publish(Crl $crl): void;

    /**
     * Get all CRLs for a CA.
     */
    public function getAll(CertificateAuthority $ca): Collection;

    /**
     * Find a CRL by its UUID.
     */
    public function findByUuid(string $uuid): ?Crl;
}
