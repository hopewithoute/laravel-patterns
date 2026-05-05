<?php

namespace App\Data;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Supports\GetActiveOrganization;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Task.
 * Handles validation and transformation for task data.
 */
class TaskData extends Data
{
    public function __construct(
        public ?string $id,
        public ?string $organization_id,
        public ?string $project_id,
        public ?string $assigned_to,
        public ?string $title,
        public ?string $description,
        public ?string $status,
        public ?string $priority,
        public ?string $due_date,
        public ?string $completed_at,
    ) {
        $this->organization_id ??= GetActiveOrganization::getSelected();
    }

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'id' => ['nullable', 'string', 'uuid', 'exists:tasks,id'],
            'organization_id' => ['nullable', 'string', 'uuid', 'exists:organizations,id'],
            'project_id' => ['sometimes', 'required', 'string', 'uuid', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'string', 'uuid', 'exists:users,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'required', 'string', 'in:'.implode(',', TaskStatus::getValues())],
            'priority' => ['sometimes', 'required', 'string', 'in:'.implode(',', Priority::getValues())],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'title.required' => 'Task title is required.',
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Selected project does not exist.',
            'status.in' => 'Invalid task status.',
            'priority.in' => 'Invalid priority level.',
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
            'organization_id' => $this->organization_id,
            'project_id' => $this->project_id,
            'assigned_to' => $this->assigned_to,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
        ];
    }
}
