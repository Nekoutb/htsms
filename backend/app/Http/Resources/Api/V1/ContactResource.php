<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Contact */
final class ContactResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'consent_status' => $this->consent_status->value,
            'consent_source' => $this->consent_source,
            'consented_at' => $this->consented_at?->toIso8601String(),
            'opted_out_at' => $this->opted_out_at?->toIso8601String(),
        ];
    }
}
