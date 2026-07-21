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
            'slug' => ['required', 'alpha_dash:ascii', 'min:2', 'max:80', Rule::unique('organizations', 'slug')],
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', Rule::in(['en', 'fr'])],
        ];
    }

    public function toData(): CreateOrganizationData
    {
        return new CreateOrganizationData(
            name: $this->string('name')->toString(),
            slug: $this->string('slug')->lower()->toString(),
            timezone: $this->string('timezone')->toString(),
            locale: $this->string('locale')->toString(),
        );
    }
}
