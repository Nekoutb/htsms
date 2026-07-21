<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Integration\ApiKeyAbility;
use App\DTO\Integration\CreateApiKeyData;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreDeveloperApiKeyRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'abilities' => ['required', 'array', 'min:1', 'max:10'],
            'abilities.*' => ['required', 'distinct', new Enum(ApiKeyAbility::class)],
            'expires_at' => ['nullable', 'date', 'after:now', 'before_or_equal:+1 year'],
        ];
    }

    public function toData(): CreateApiKeyData
    {
        /** @var list<string> $abilities */
        $abilities = $this->validated('abilities');
        $expiresAt = $this->validated('expires_at');

        return new CreateApiKeyData(
            name: $this->string('name')->toString(),
            abilities: $abilities,
            expiresAt: is_string($expiresAt) ? new DateTimeImmutable($expiresAt) : null,
        );
    }
}
