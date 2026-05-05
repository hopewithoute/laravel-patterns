<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Organization.
 * Handles validation and transformation for organization data.
 */
class OrganizationData extends Data
{
    public function __construct(
        public ?string $id,
        public string $name,
        public ?string $description,
        public ?string $logo,
        public bool $is_active = true,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'id' => ['nullable', 'string', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'name.required' => 'Organization name is required.',
            'name.max' => 'Organization name cannot exceed 255 characters.',
        ];
    }

    public static function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for storage.
     */
    public function toModelData(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'logo' => $this->logo,
            'is_active' => $this->is_active,
        ];
    }
}
