<?php

declare(strict_types=1);

namespace CA\Crl\Console\Commands;

use CA\Crl\Contracts\CrlManagerInterface;
use CA\Models\CertificateAuthority;
use Illuminate\Console\Command;

class CrlPublishCommand extends Command
{
    protected $signature = 'ca:crl:publish
        {ca_uuid : The UUID of the Certificate Authority}';

    protected $description = 'Publish the current CRL to storage';

    public function handle(CrlManagerInterface $crlManager): int
    {
        $caUuid = $this->argument('ca_uuid');

        $ca = CertificateAuthority::where('id', $caUuid)->first();

        if ($ca === null) {
            $this->error("Certificate Authority [{$caUuid}] not found.");

            return self::FAILURE;
        }

        $crl = $crlManager->getCurrent($ca);

        if ($crl === null) {
            $this->error("No current CRL found for CA [{$caUuid}].");

            return self::FAILURE;
        }

        try {
            $crlManager->publish($crl);

            $this->info("CRL #{$crl->crl_number} published successfully.");
            $this->line("Storage path: {$crl->fresh()->storage_path}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to publish CRL: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
