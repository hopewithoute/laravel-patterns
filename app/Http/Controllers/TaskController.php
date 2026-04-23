<?php

namespace App\Http\Controllers;

use App\Actions\TaskAssignAction;
use App\Actions\TaskCompleteAction;
use App\Actions\TaskCreateAction;
use App\Actions\TaskDeleteAction;
use App\Actions\TaskMoveAction;
use App\Actions\TaskStatusUpdateAction;
use App\Actions\TaskUpdateAction;
use App\Data\KanbanData;
use App\Data\TaskAssignData;
use App\Data\TaskData;
use App\Data\TaskMoveData;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\QueryBuilders\TaskIndexQuery;
use App\QueryBuilders\TaskKanbanQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index(TaskIndexQuery $query): Response
    {
        return Inertia::render('Task/Index', [
            'tasks' => $query->paginate(15),
            'filters' => [
                'statuses' => TaskStatus::asOptions(),
                'priorities' => Priority::asOptions(),
            ],
        ]);
    }

    public function kanban(KanbanData $data, TaskKanbanQuery $query): JsonResponse
    {
        return response()->json(
            $query->getBoard($data->start_date, $data->end_date)
        );
    }

    public function move(Task $task, TaskMoveData $data, TaskMoveAction $action): JsonResponse
    {
        $this->authorize('update', $task);

        $updated = $action->execute($task, $data);

        return response()->json([
            'task' => (new TaskKanbanQuery(request()))->serializeTask($updated),
            'message' => 'Task moved.',
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(): Response
    {
        return Inertia::render('Task/Form', [
            'options' => $this->getFormOptions(),
        ]);
    }

    /**
     * Store a newly created task.
     */
    public function store(TaskData $data, TaskCreateAction $action): RedirectResponse
    {
        $task = $action->execute($data);

        return redirect()
            ->route('tasks.show', $task)
            ->with('message', [
                'type' => 'success',
                'text' => 'Task created successfully.',
            ]);
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): Response
    {
        $task->load(['project', 'assignee', 'comments.user']);

        return Inertia::render('Task/Show', [
            'task' => $task,
        ]);
    }

    /**
     * Show the form for editing the task.
     */
    public function edit(Task $task): Response
    {
        return Inertia::render('Task/Form', [
            'task' => $task,
            'options' => $this->getFormOptions(),
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(
        TaskData $data,
        Task $task,
        TaskUpdateAction $action
    ): RedirectResponse {
        $action->execute($data, $task);

        return redirect()
            ->route('tasks.show', $task)
            ->with('message', [
                'type' => 'success',
                'text' => 'Task updated successfully.',
            ]);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task, TaskDeleteAction $action): RedirectResponse
    {
        $action->execute($task);

        $redirectTo = request()->input('redirect_to');

        // Smart fallback: If deleted from its own show page, determine best return path
        if (! $redirectTo && url()->previous() === route('tasks.show', $task)) {
            $redirectTo = $task->project_id
                ? route('projects.show', $task->project_id)
                : route('tasks.index');
        }

        return ($redirectTo ? redirect()->to($redirectTo) : redirect()->back())
            ->with('message', [
                'type' => 'success',
                'text' => 'Task deleted successfully.',
            ]);
    }

    /**
     * Mark task as completed.
     */
    public function complete(Task $task, TaskCompleteAction $action): RedirectResponse
    {
        $this->authorize('update', $task);

        $action->execute($task);

        return redirect()
            ->back()
            ->with('success', 'Task marked as completed.');
    }

    /**
     * Update task status.
     */
    public function status(Task $task, TaskStatusUpdateAction $action): RedirectResponse
    {
        $this->authorize('update', $task);

        $status = request()->input('status');
        $action->execute($task, $status);

        return redirect()
            ->back()
            ->with('success', "Task status updated to {$status}.");
    }

    /**
     * Assign task to a user.
     */
    public function assign(Task $task, TaskAssignData $data, TaskAssignAction $action): RedirectResponse
    {
        $action->execute($task, $data);

        return redirect()
            ->back()
            ->with('success', 'Task assigned successfully.');
    }

    /**
     * Get options for task form.
     */
    private function getFormOptions(): array
    {
        return [
            'projects' => Project::query()
                ->active()
                ->select('id', 'name')
                ->get(),
            'users' => User::query()
                ->active()
                ->select('id', 'name')
                ->get(),
            'statuses' => TaskStatus::asOptions(),
            'priorities' => Priority::asOptions(),
        ];
    }
}
