<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'content' => ['required', 'string', 'max:1600'],
            'send_at' => ['nullable', 'date', 'after:now'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'sim_slot' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}
