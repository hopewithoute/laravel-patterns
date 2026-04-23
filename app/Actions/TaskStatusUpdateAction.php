<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to update a task's status.
 */
class TaskStatusUpdateAction
{
    public function execute(Task $task, string $status): Task
    {
        return DB::transaction(function () use ($task, $status) {
            $task->update([
                'status' => $status,
                'completed_at' => $status === TaskStatus::Done ? now() : null,
            ]);

            return $task->fresh();
        });
    }
}
