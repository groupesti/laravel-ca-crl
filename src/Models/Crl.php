<?php

declare(strict_types=1);

namespace CA\Crl\Models;

use CA\Models\CertificateAuthority;
use CA\Traits\Auditable;
use CA\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Crl extends Model
{
    use HasUuids;
    use BelongsToTenant;
    use Auditable;

    protected $table = 'ca_crls';

    protected $fillable = [
        'uuid',
        'ca_id',
        'tenant_id',
        'crl_number',
        'this_update',
        'next_update',
        'crl_pem',
        'crl_der',
        'signature_algorithm',
        'entries_count',
        'is_delta',
        'base_crl_number',
        'storage_path',
    ];

    protected $hidden = [
        'crl_der',
    ];

    protected function casts(): array
    {
        return [
            'crl_number' => 'integer',
            'this_update' => 'datetime',
            'next_update' => 'datetime',
            'entries_count' => 'integer',
            'is_delta' => 'boolean',
            'base_crl_number' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // ---- Relationships ----

    public function certificateAuthority(): BelongsTo
    {
        return $this->belongsTo(CertificateAuthority::class, 'ca_id');
    }

    // ---- Scopes ----

    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->where('is_delta', false)
            ->where('next_update', '>', Carbon::now())
            ->orderByDesc('crl_number');
    }

    public function scopeDelta(Builder $query): Builder
    {
        return $query->where('is_delta', true);
    }

    public function scopeFull(Builder $query): Builder
    {
        return $query->where('is_delta', false);
    }

    public function scopeForCa(Builder $query, string $caId): Builder
    {
        return $query->where('ca_id', $caId);
    }

    // ---- Helpers ----

    public function isExpired(): bool
    {
        return $this->next_update !== null && $this->next_update->isPast();
    }

    public function isCurrent(): bool
    {
        return !$this->isExpired()
            && !$this->is_delta
            && $this->this_update !== null
            && $this->this_update->isPast();
    }
}
