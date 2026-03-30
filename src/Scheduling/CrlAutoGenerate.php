<?php

declare(strict_types=1);

namespace CA\Crl\Scheduling;

use CA\Crl\Contracts\CrlManagerInterface;
use CA\Models\CertificateStatus;
use CA\Models\CertificateAuthority;
use Illuminate\Support\Facades\Log;

class CrlAutoGenerate
{
    public function __construct(
        private readonly CrlManagerInterface $crlManager,
    ) {}

    /**
     * Invokable handler for the Laravel scheduler.
     *
     * Finds all active CAs and generates fresh CRLs for those whose current CRL
     * is expiring within the configured overlap_hours window.
     */
    public function __invoke(): void
    {
        $overlapHours = (int) config('ca-crl.overlap_hours', 6);

        $cas = CertificateAuthority::query()
            ->where('status', CertificateStatus::ACTIVE)
            ->get();

        foreach ($cas as $ca) {
            try {
                $currentCrl = $this->crlManager->getCurrent($ca);

                // Generate a new CRL if there is no current CRL, or it is expiring soon
                $shouldGenerate = $currentCrl === null
                    || $currentCrl->next_update->subHours($overlapHours)->isPast();

                if ($shouldGenerate) {
                    $newCrl = $this->crlManager->generate($ca);

                    Log::info("Auto-generated CRL #{$newCrl->crl_number} for CA [{$ca->getId()}].");

                    // Auto-publish to storage
                    $this->crlManager->publish($newCrl);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to auto-generate CRL for CA [{$ca->getId()}]: {$e->getMessage()}");
            }
        }
    }
}
