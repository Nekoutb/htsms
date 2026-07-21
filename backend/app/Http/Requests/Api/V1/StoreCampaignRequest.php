<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:1600'],
            'contact_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'contact_ids.*' => ['required', 'string', 'distinct'],
            'send_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
