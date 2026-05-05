<?php

namespace App\Http\Controllers\Api;

use App\Actions\TaskCreateAction;
use App\Actions\TaskUpdateAction;
use App\Data\TaskData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TaskResource;
use App\Models\Task;
use App\QueryBuilders\TaskIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request, TaskIndexQuery $query): AnonymousResourceCollection
    {
        $organizationId = $request->header('X-Organization');

        $tasks = $query->where('tasks.organization_id', $organizationId)
            ->paginate($request->input('per_page', 15));

        return TaskResource::collection($tasks);
    }

    public function store(Request $request, TaskCreateAction $action): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|string|in:Low,Medium,High,Urgent',
            'status' => 'required|string|in:Todo,In Progress,Review,Done',
            'due_date' => 'nullable|date',
        ]);

        $validated['organization_id'] = $request->header('X-Organization');

        $task = $action->execute(TaskData::from($validated));

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        return TaskResource::make($task);
    }

    public function update(Request $request, Task $task, TaskUpdateAction $action): TaskResource
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'sometimes|required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'sometimes|required|string|in:Low,Medium,High,Urgent',
            'status' => 'sometimes|required|string|in:Todo,In Progress,Review,Done',
            'due_date' => 'nullable|date',
        ]);

        // Merge existing task data with validated input
        $data = array_merge($task->toArray(), $validated);
        $data['organization_id'] = $task->organization_id;

        $task = $action->execute(TaskData::from($data), $task);

        return TaskResource::make($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(null, 204);
    }
}
