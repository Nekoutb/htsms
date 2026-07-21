<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PublicHttpsUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) > 2048) {
            $fail('The :attribute must be a valid HTTPS URL.');

            return;
        }
        $parts = parse_url($value);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;
        $scheme = is_array($parts) ? ($parts['scheme'] ?? null) : null;
        if ($scheme !== 'https' || ! is_string($host) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            $fail('The :attribute must be an HTTPS URL without embedded credentials.');

            return;
        }
        if (! $this->hostIsPublic($host)) {
            $fail('The :attribute must resolve only to public internet addresses.');
        }
    }

    public function hostIsPublic(string $host): bool
    {
        $normalized = strtolower(rtrim($host, '.'));
        if ($normalized === 'localhost' || str_ends_with($normalized, '.localhost') || str_ends_with($normalized, '.local')) {
            return false;
        }
        $addresses = filter_var($normalized, FILTER_VALIDATE_IP) ? [$normalized] : gethostbynamel($normalized);
        if (! is_array($addresses) || $addresses === []) {
            return false;
        }
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }
}
