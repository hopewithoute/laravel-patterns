<?php

namespace App\Actions;

use App\Data\TaskMoveData;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to update task due_date and sort_order during drag-and-drop.
 */
readonly class TaskMoveAction
{
    public function execute(Task $task, TaskMoveData $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            // Shift sort_order of siblings in target column
            Task::whereDate('due_date', $data->due_date)
                ->where('id', '!=', $task->getKey())
                ->where('sort_order', '>=', $data->sort_order)
                ->increment('sort_order');

            $task->update([
                'due_date' => $data->due_date,
                'sort_order' => $data->sort_order,
            ]);

            return $task->fresh();
        });
    }
}
