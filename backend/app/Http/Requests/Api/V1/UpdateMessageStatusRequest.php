<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMessageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['dispatched', 'sent', 'delivered', 'retry_pending', 'failed'])],
            'failure_code' => ['nullable', 'string', 'max:80', 'required_if:status,failed'],
            'failure_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
