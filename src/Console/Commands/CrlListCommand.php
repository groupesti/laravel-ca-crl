<?php

declare(strict_types=1);

namespace CA\Crl\Console\Commands;

use CA\Crl\Models\Crl;
use Illuminate\Console\Command;

class CrlListCommand extends Command
{
    protected $signature = 'ca:crl:list
        {--ca= : Filter by Certificate Authority UUID}
        {--tenant= : Filter by tenant ID}';

    protected $description = 'List all CRLs';

    public function handle(): int
    {
        $query = Crl::query()->orderByDesc('created_at');

        $caId = $this->option('ca');
        $tenantId = $this->option('tenant');

        if ($caId !== null) {
            $query->where('ca_id', $caId);
        }

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $crls = $query->get();

        if ($crls->isEmpty()) {
            $this->info('No CRLs found.');

            return self::SUCCESS;
        }

        $rows = $crls->map(fn (Crl $crl): array => [
            $crl->uuid,
            $crl->ca_id,
            (string) $crl->crl_number,
            $crl->this_update?->toDateTimeString() ?? '-',
            $crl->next_update?->toDateTimeString() ?? '-',
            (string) $crl->entries_count,
            $crl->is_delta ? 'Delta' : 'Full',
            $crl->isExpired() ? 'Expired' : 'Active',
        ])->all();

        $this->table(
            ['UUID', 'CA ID', 'CRL #', 'This Update', 'Next Update', 'Entries', 'Type', 'Status'],
            $rows,
        );

        return self::SUCCESS;
    }
}
