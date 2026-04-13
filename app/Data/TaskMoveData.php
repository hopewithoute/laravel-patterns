<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class TaskMoveData extends Data
{
    public function __construct(
        public ?string $due_date,
        public int $sort_order,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'due_date' => ['nullable', 'date'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
