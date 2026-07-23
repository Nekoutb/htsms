<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTO\Identity\CreateOrganizationData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => ['nullable', 'alpha_dash:ascii', 'min:2', 'max:80', Rule::unique('organizations', 'slug')],
            'timezone' => ['nullable', 'timezone:all'],
            'locale' => ['nullable', Rule::in(['en', 'fr'])],
        ];
    }

    public function toData(): CreateOrganizationData
    {
        $slug = $this->string('slug')->lower()->trim()->toString();

        return new CreateOrganizationData(
            name: $this->string('name')->trim()->toString(),
            slug: $slug === '' ? null : $slug,
            timezone: $this->filled('timezone') ? $this->string('timezone')->toString() : 'Africa/Douala',
            locale: $this->filled('locale') ? $this->string('locale')->toString() : 'en',
        );
    }
}
