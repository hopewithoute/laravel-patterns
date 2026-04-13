<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Project.
 * Handles validation and transformation for project data.
 */
class ProjectData extends Data
{
    public function __construct(
        public ?string $id,
        public ?string $organization_id,
        public string $name,
        public ?string $description,
        public ?string $color,
        public bool $is_active = true,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'id' => ['nullable', 'string', 'uuid', 'exists:projects,id'],
            'organization_id' => ['nullable', 'string', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'name.required' => 'Project name is required.',
            'name.max' => 'Project name cannot exceed 255 characters.',
            'color.regex' => 'Color must be a valid hex color (e.g., #FF5733).',
        ];
    }

    public static function authorize(): bool
    {
        return true;
    }
}
