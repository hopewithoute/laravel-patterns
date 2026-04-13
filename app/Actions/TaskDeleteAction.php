<?php

namespace App\Actions;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to delete a task.
 */
readonly class TaskDeleteAction
{
    public function execute(Task $task): void
    {
        DB::transaction(function () use ($task) {
            // Delete associated comments first
            $task->comments()->delete();

            // Delete the task
            $task->delete();
        });
    }
}
