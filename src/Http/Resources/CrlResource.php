<?php

declare(strict_types=1);

namespace CA\Crl\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrlResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'ca_id' => $this->ca_id,
            'crl_number' => $this->crl_number,
            'this_update' => $this->this_update?->toIso8601String(),
            'next_update' => $this->next_update?->toIso8601String(),
            'entries_count' => $this->entries_count,
            'is_delta' => $this->is_delta,
            'signature_algorithm' => $this->signature_algorithm,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
