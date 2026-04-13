<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class TaskAssignData extends Data
{
    public function __construct(
        public string $assigned_to,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'assigned_to' => ['required', 'string', 'uuid', 'exists:users,id'],
        ];
    }
}
