<?php

namespace App\Http\Controllers\Api;

use App\Actions\TaskCreateAction;
use App\Actions\TaskDeleteAction;
use App\Actions\TaskUpdateAction;
use App\Data\TaskData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TaskResource;
use App\Models\Task;
use App\QueryBuilders\TaskIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(TaskIndexQuery $query): AnonymousResourceCollection
    {
        return TaskResource::collection($query->jsonPaginate());
    }

    public function store(TaskData $data, TaskCreateAction $action): JsonResponse
    {
        $task = $action->execute($data);

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        return TaskResource::make($task->load(['project', 'assignee', 'comments.user', 'organization']));
    }

    public function update(TaskData $data, Task $task, TaskUpdateAction $action): TaskResource
    {
        $task = $action->execute($data, $task);

        return TaskResource::make($task);
    }

    public function destroy(Task $task, TaskDeleteAction $action): JsonResponse
    {
        $action->execute($task);

        return response()->json(null, 204);
    }
}
