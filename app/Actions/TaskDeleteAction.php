<?php

namespace App\Actions;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to delete a task.
 */
class TaskDeleteAction
{
    public function execute(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $task->delete();
        });
    }
}
