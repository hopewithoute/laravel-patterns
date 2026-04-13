<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class KanbanData extends Data
{
    public function __construct(
        public string $start_date,
        public string $end_date,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
