<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class WorkspaceCreateData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $invite_emails,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'invite_emails' => ['nullable', 'string'],
        ];
    }
}
