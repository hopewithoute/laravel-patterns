<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Comment.
 */
class CommentData extends Data
{
    public function __construct(
        public ?string $id,
        public ?string $organization_id,
        public ?string $task_id,
        public string $content,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'id' => ['nullable', 'string', 'uuid', 'exists:comments,id'],
            'organization_id' => ['nullable', 'string', 'uuid', 'exists:organizations,id'],
            'task_id' => ['nullable', 'string', 'uuid', 'exists:tasks,id'],
            'content' => ['required', 'string', 'max:2000'],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'content.required' => 'Comment content is required.',
            'content.max' => 'Comment cannot exceed 2000 characters.',
        ];
    }

    /**
     * Prepare data for storage.
     */
    public function toModelData(): array
    {
        return [
            'organization_id' => $this->organization_id,
            'task_id' => $this->task_id,
            'content' => $this->content,
        ];
    }
}
