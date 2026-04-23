<?php

namespace App\Actions;

use App\Data\TaskData;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new task.
 */
readonly class TaskCreateAction
{
    public function __construct(
        private readonly Task $model,
    ) {}

    public function execute(TaskData $data): Task
    {
        return DB::transaction(function () use ($data) {
            return $this->model->create($data->toModelData());
        });
    }
}
