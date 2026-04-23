<?php

namespace App\Actions;

use App\Data\TaskData;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to update an existing task.
 */
class TaskUpdateAction
{
    public function execute(TaskData $data, Task $task): Task
    {
        return DB::transaction(function () use ($data, $task) {
            $updateData = $data->toModelData();

            // Handle completed_at based on status
            if ($data->status === TaskStatus::Done && ! $task->completed_at) {
                $updateData['completed_at'] = now();
            } elseif ($data->status !== TaskStatus::Done) {
                $updateData['completed_at'] = null;
            }

            $task->update($updateData);

            return $task->fresh();
        });
    }
}
