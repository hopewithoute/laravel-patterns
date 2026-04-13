<?php

namespace App\Actions;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to mark a task as completed.
 */
readonly class TaskCompleteAction
{
    public function execute(Task $task): Task
    {
        return DB::transaction(function () use ($task) {
            $task->markAsCompleted();

            return $task->fresh();
        });
    }
}
