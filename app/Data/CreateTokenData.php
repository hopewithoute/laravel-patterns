<?php

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for creating API tokens.
 */
class CreateTokenData extends Data
{
    public function __construct(
        public string $name,
        public ?array $abilities,
        public ?Carbon $expires_at,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'name.required' => 'Token name is required.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }

    public static function authorize(): bool
    {
        return true;
    }
}
