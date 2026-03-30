<?php

declare(strict_types=1);

namespace CA\Crl\Contracts;

use CA\Crl\Models\Crl;
use CA\Models\CertificateAuthority;

interface CrlDistributionInterface
{
    /**
     * Publish a CRL to storage and return the URL.
     */
    public function publish(Crl $crl): string;

    /**
     * Get the distribution point URL for a CA's CRL.
     */
    public function getDistributionUrl(CertificateAuthority $ca): ?string;

    /**
     * Serve the current CRL as DER-encoded bytes.
     */
    public function serve(CertificateAuthority $ca): string;
}
