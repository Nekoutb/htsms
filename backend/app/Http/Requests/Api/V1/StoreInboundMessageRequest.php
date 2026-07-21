<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInboundMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'device_event_id' => ['required', 'string', 'min:8', 'max:100'],
            'sender' => ['required', 'string', 'min:3', 'max:32'],
            'recipient' => ['nullable', 'string', 'max:32'],
            'content' => ['required', 'string', 'max:10000'],
            'received_at' => ['required', 'date', 'before_or_equal:+5 minutes', 'after:-30 days'],
            'sim_slot_index' => ['required', 'integer', 'min:0', 'max:3'],
        ];
    }
}
