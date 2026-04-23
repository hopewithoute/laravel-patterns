<?php

namespace App\AI\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class LookupWorkspaceUsersToolData extends Data
{
    public function __construct(
        public ?string $query = null,
        public ?int $limit = null,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
