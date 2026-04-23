<?php

namespace App\AI\Actions;

use App\Actions\TaskCreateForOrganizationAction;
use App\AI\Data\CreateTaskToolData;
use App\AI\Exceptions\AiToolException;
use App\Data\TaskData;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Validation\ValidationException;

class TaskCreateFromAiAction
{
    public function __construct(
        private TaskCreateForOrganizationAction $taskCreateForOrganizationAction,
        private ResolveWorkspaceProjectReferenceAction $resolveWorkspaceProjectReferenceAction,
        private ResolveWorkspaceAssigneeReferenceAction $resolveWorkspaceAssigneeReferenceAction,
    ) {}

    public function execute(CreateTaskToolData $data, string $organizationId, User $actor): Task
    {
        try {
            if (($data->assign_to_me ?? false) && $data->assigned_to !== null && trim($data->assigned_to) !== '') {
                throw new AiToolException('Use either assign_to_me or assigned_to when creating a task, not both.');
            }

            $project = $this->resolveWorkspaceProjectReferenceAction->execute($data->project, $organizationId);
            $assignee = $this->resolveWorkspaceAssigneeReferenceAction->execute(
                $data->assigned_to,
                (bool) ($data->assign_to_me ?? false),
                $organizationId,
                $actor,
            );

            return $this->taskCreateForOrganizationAction->execute(
                TaskData::validateAndCreate([
                    'id' => null,
                    'organization_id' => $organizationId,
                    'project_id' => $project->id,
                    'assigned_to' => $assignee?->id,
                    'title' => $data->title,
                    'description' => $data->description,
                    'status' => TaskStatus::Todo,
                    'priority' => $data->priority ?? Priority::Medium,
                    'due_date' => $this->normalizeDueDate($data->due_date),
                    'completed_at' => null,
                ]),
                $organizationId,
            );
        } catch (AiToolException $exception) {
            throw $exception;
        } catch (ValidationException $exception) {
            throw new AiToolException($this->firstValidationMessage($exception));
        }
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        return (string) (collect($exception->errors())->flatten()->first() ?? 'Unable to create task.');
    }

    private function normalizeDueDate(?string $dueDate): ?string
    {
        if ($dueDate === null || trim($dueDate) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($dueDate));

        return match ($normalized) {
            'today', 'hari ini' => now()->toDateString(),
            'tomorrow', 'besok' => now()->addDay()->toDateString(),
            default => $this->parseDate($dueDate),
        };
    }

    private function parseDate(string $dueDate): string
    {
        try {
            return CarbonImmutable::parse($dueDate)->toDateString();
        } catch (InvalidFormatException) {
            throw new AiToolException('Due date is not recognized. Use a concrete date like 2026-04-30, today, tomorrow, hari ini, or besok.');
        }
    }
}
