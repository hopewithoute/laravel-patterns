<?php

namespace App\Actions;

use App\Data\TaskData;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TaskCreateForOrganizationAction
{
    public function __construct(
        private Project $projectModel,
        private User $userModel,
        private TaskCreateAction $taskCreateAction,
    ) {}

    public function execute(TaskData $data, string $organizationId): Task
    {
        $this->ensureProjectExists($data->project_id, $organizationId);
        $this->ensureAssigneeExists($data->assigned_to, $organizationId);

        return $this->taskCreateAction->execute(
            $this->normalizeTaskData($data, $organizationId),
        );
    }

    private function normalizeTaskData(TaskData $data, string $organizationId): TaskData
    {
        return TaskData::validateAndCreate([
            'id' => $data->id,
            'organization_id' => $organizationId,
            'project_id' => $data->project_id,
            'assigned_to' => $data->assigned_to,
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
            'priority' => $data->priority,
            'due_date' => $data->due_date,
            'completed_at' => $data->completed_at,
        ]);
    }

    private function ensureProjectExists(string $projectId, string $organizationId): void
    {
        $projectExists = $this->projectModel
            ->newQuery()
            ->withoutOrganizationScope()
            ->whereKey($projectId)
            ->where('organization_id', $organizationId)
            ->exists();

        if ($projectExists) {
            return;
        }

        throw ValidationException::withMessages([
            'project_id' => 'Selected project is not available in the active organization.',
        ]);
    }

    private function ensureAssigneeExists(?string $assigneeId, string $organizationId): void
    {
        if ($assigneeId === null) {
            return;
        }

        $assigneeExists = $this->userModel
            ->newQuery()
            ->whereKey($assigneeId)
            ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organizationId))
            ->exists();

        if ($assigneeExists) {
            return;
        }

        throw ValidationException::withMessages([
            'assigned_to' => 'Selected assignee is not available in the active organization.',
        ]);
    }
}
