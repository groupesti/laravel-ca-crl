<?php

declare(strict_types=1);

namespace CA\Crl\Services;

use CA\Crl\Contracts\CrlDistributionInterface;
use CA\Crl\Models\Crl;
use CA\Exceptions\CrlException;
use CA\Log\Facades\CaLog;
use CA\Models\CertificateAuthority;
use Illuminate\Support\Facades\Storage;

class CrlDistributor implements CrlDistributionInterface
{
    public function publish(Crl $crl): string
    {
        $disk = $this->getDisk();
        $basePath = rtrim((string) config('ca-crl.storage_path', 'ca/crls'), '/');

        $caUuid = $crl->certificateAuthority->id;
        $filePath = "{$basePath}/{$caUuid}/crl_{$crl->crl_number}.crl";

        try {
            $disk->put($filePath, $crl->crl_der);
        } catch (\Throwable $e) {
            CaLog::critical($e->getMessage(), ['operation' => 'crl_distribution', 'exception' => $e::class, 'crl_uuid' => $crl->uuid]);

            throw new CrlException(
                "Failed to publish CRL [{$crl->uuid}] to storage: {$e->getMessage()}",
                previous: $e,
            );
        }

        $crl->update(['storage_path' => $filePath]);

        $url = $this->buildUrl($filePath);

        CaLog::crlUpdated($caUuid, $crl->entries_count ?? 0, [
            'crl_uuid' => $crl->uuid,
            'crl_number' => $crl->crl_number,
            'storage_path' => $filePath,
            'distribution_url' => $url,
            'operation' => 'crl_publish',
        ]);

        return $url;
    }

    public function getDistributionUrl(CertificateAuthority $ca): ?string
    {
        $configuredUrl = config('ca-crl.distribution_url');

        if ($configuredUrl !== null) {
            return rtrim((string) $configuredUrl, '/') . '/' . $ca->getId() . '/current';
        }

        try {
            return route('ca.crl.current', ['caId' => $ca->getId()]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function serve(CertificateAuthority $ca): string
    {
        // First try to load from storage
        $crl = Crl::query()
            ->where('ca_id', $ca->getId())
            ->where('is_delta', false)
            ->whereNotNull('storage_path')
            ->orderByDesc('crl_number')
            ->first();

        if ($crl !== null && $crl->storage_path !== null) {
            $disk = $this->getDisk();

            if ($disk->exists($crl->storage_path)) {
                return $disk->get($crl->storage_path);
            }
        }

        // Fall back to database-stored DER
        $crl = Crl::query()
            ->where('ca_id', $ca->getId())
            ->where('is_delta', false)
            ->orderByDesc('crl_number')
            ->first();

        if ($crl === null || $crl->crl_der === null) {
            $message = "No CRL available for CA [{$ca->getId()}].";
            CaLog::critical($message, ['operation' => 'crl_distribution', 'exception' => CrlException::class]);

            throw new CrlException($message);
        }

        return $crl->crl_der;
    }

    /**
     * Get the configured storage disk.
     */
    private function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $diskName = config('ca-crl.storage_disk', 'local');

        return Storage::disk($diskName);
    }

    /**
     * Build a URL for a stored CRL file.
     */
    private function buildUrl(string $filePath): string
    {
        $configuredUrl = config('ca-crl.distribution_url');

        if ($configuredUrl !== null) {
            return rtrim((string) $configuredUrl, '/') . '/' . ltrim($filePath, '/');
        }

        $disk = $this->getDisk();

        try {
            return $disk->url($filePath);
        } catch (\Throwable) {
            return $filePath;
        }
    }
}
