<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTO\Identity\LoginData;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
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
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
            'device_name' => ['required', 'string', 'min:2', 'max:80'],
        ];
    }

    public function toData(): LoginData
    {
        return new LoginData(
            email: $this->string('email')->trim()->lower()->toString(),
            password: $this->string('password')->toString(),
            deviceName: $this->string('device_name')->trim()->toString(),
        );
    }
}
