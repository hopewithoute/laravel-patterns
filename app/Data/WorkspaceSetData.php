<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class WorkspaceSetData extends Data
{
    public function __construct(
        public string $organization_id,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'organization_id' => ['required', 'string', 'uuid', 'exists:organizations,id'],
        ];
    }
}
