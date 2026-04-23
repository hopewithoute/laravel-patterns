<?php

namespace App\Actions;

use App\Data\TaskAssignData;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to assign a task to a user.
 */
class TaskAssignAction
{
    public function execute(Task $task, TaskAssignData $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $task->update(['assigned_to' => $data->assigned_to]);

            return $task->fresh();
        });
    }
}
