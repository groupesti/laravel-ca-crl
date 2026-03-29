<?php

declare(strict_types=1);

namespace CA\Crl\Console\Commands;

use CA\Crl\Contracts\CrlManagerInterface;
use CA\Models\CertificateAuthority;
use Illuminate\Console\Command;

class CrlGenerateCommand extends Command
{
    protected $signature = 'ca:crl:generate
        {ca_uuid : The UUID of the Certificate Authority}
        {--delta : Generate a delta CRL instead of a full CRL}';

    protected $description = 'Generate a new CRL for a Certificate Authority';

    public function handle(CrlManagerInterface $crlManager): int
    {
        $caUuid = $this->argument('ca_uuid');
        $isDelta = $this->option('delta');

        $ca = CertificateAuthority::where('id', $caUuid)->first();

        if ($ca === null) {
            $this->error("Certificate Authority [{$caUuid}] not found.");

            return self::FAILURE;
        }

        try {
            if ($isDelta) {
                $baseCrl = $crlManager->getCurrent($ca);

                if ($baseCrl === null) {
                    $this->error('No current full CRL exists to base a delta CRL on.');

                    return self::FAILURE;
                }

                $crl = $crlManager->generateDelta($ca, $baseCrl);
                $this->info("Delta CRL generated successfully.");
            } else {
                $crl = $crlManager->generate($ca);
                $this->info("Full CRL generated successfully.");
            }

            $this->table(
                ['Field', 'Value'],
                [
                    ['UUID', $crl->uuid],
                    ['CRL Number', (string) $crl->crl_number],
                    ['This Update', $crl->this_update->toIso8601String()],
                    ['Next Update', $crl->next_update->toIso8601String()],
                    ['Entries', (string) $crl->entries_count],
                    ['Is Delta', $crl->is_delta ? 'Yes' : 'No'],
                    ['Algorithm', $crl->signature_algorithm],
                ],
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to generate CRL: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
