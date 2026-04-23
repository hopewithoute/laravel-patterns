<?php

namespace App\AI\Data;

use App\Enums\Priority;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CreateTaskToolData extends Data
{
    public function __construct(
        public string $title,
        public ?string $project = null,
        public ?string $description = null,
        public ?string $priority = null,
        public ?string $due_date = null,
        public ?bool $assign_to_me = null,
        public ?string $assigned_to = null,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', 'in:'.implode(',', Priority::getValues())],
            'due_date' => ['nullable', 'string', 'max:255'],
            'assign_to_me' => ['nullable', 'boolean'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
        ];
    }
}
