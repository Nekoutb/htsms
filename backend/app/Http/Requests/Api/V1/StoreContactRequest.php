<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'name' => ['nullable', 'string', 'max:160'],
            'attributes' => ['nullable', 'array', 'max:30'],
            'consent_status' => ['required', Rule::in(['unknown', 'consented', 'opted_out'])],
            'consent_source' => ['required_if:consent_status,consented', 'nullable', 'string', 'max:120'],
        ];
    }
}
