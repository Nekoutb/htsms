<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class PairDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'pairing_token' => ['required', 'string', 'regex:/^htsms_pair_[A-HJ-NP-Z2-9]{8}$/', 'max:32'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'manufacturer' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:120'],
            'android_version' => ['required', 'string', 'max:40'],
            'app_version' => ['required', 'string', 'max:40'],
            'fcm_token' => ['nullable', 'string', 'max:4096'],
            'sims' => ['required', 'array', 'min:1', 'max:4'],
            'sims.*.slot_index' => ['required', 'integer', 'min:0', 'max:3', 'distinct'],
            'sims.*.carrier_name' => ['nullable', 'string', 'max:120'],
            'sims.*.phone_number' => ['nullable', 'string', 'max:32'],
            'sims.*.is_active' => ['required', 'boolean'],
        ];
    }
}
