<?php

namespace App\AI\Runtime\Tools\Registry;

use App\AI\Actions\TaskCreateFromAiAction;
use App\AI\Data\CreateTaskToolData;
use App\AI\Exceptions\AiToolException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

readonly class CreateTaskToolExecutor
{
    public function __construct(
        private TaskCreateFromAiAction $taskCreateFromAiAction,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(array $input, string $organizationId): array
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            throw new AiToolException('Authenticated workspace user context is required before creating a task.');
        }

        $task = $this->taskCreateFromAiAction->execute(
            CreateTaskToolData::validateAndCreate($input),
            $organizationId,
            $actor,
        );
        $task->loadMissing(['project:id,name', 'assignee:id,name']);

        return [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'project_name' => $task->project?->name,
            'title' => $task->title,
            'status' => $task->getRawOriginal('status'),
            'priority' => $task->priority?->value,
            'due_date' => $task->due_date?->toDateString(),
            'assigned_to' => $task->assigned_to,
            'assigned_to_name' => $task->assignee?->name,
        ];
    }
}
