<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Integration\WebhookEvent;
use App\Rules\PublicHttpsUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'url' => ['required', 'string', new PublicHttpsUrl],
            'events' => ['required', 'array', 'min:1', 'max:10'],
            'events.*' => ['required', 'distinct', new Enum(WebhookEvent::class)],
        ];
    }

    /** @return list<string> */
    public function eventValues(): array
    {
        $values = $this->validated('events');

        return is_array($values) ? array_values(array_filter($values, 'is_string')) : [];
    }
}
