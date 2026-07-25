<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DeviceHeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'app_version' => ['required', 'string', 'max:40'],
            'android_version' => ['required', 'string', 'max:40'],
            'battery_percent' => ['required', 'integer', 'between:0,100'],
            'connection_type' => ['required', Rule::in(['wifi', 'cellular', 'ethernet', 'offline', 'other'])],
            'fcm_token' => ['nullable', 'string', 'max:4096'],
            'sims' => ['required', 'array', 'min:1', 'max:4'],
            'sims.*.slot_index' => ['required', 'integer', 'min:0', 'max:3', 'distinct'],
            'sims.*.carrier_name' => ['nullable', 'string', 'max:120'],
            'sims.*.phone_number' => ['nullable', 'string', 'max:32'],
            'sims.*.is_active' => ['required', 'boolean'],
        ];
    }
}
