# Thin Controller Pattern

> **Controller as Orchestrator, Not Implementor**

## Overview

Controllers in this project follow the **"Thin Controller, Fat Domain"** principle. Controllers contain no business logic — they only serve as **orchestrators** that:

1. Receive requests (auto-resolved DTO)
2. Call Actions / QueryBuilders
3. Return Inertia responses

## Example: TaskController

```php
class TaskController extends Controller
{
    // ── INDEX ─────────────────────────────────
    // QueryBuilder is injected, automatically handles filter/sort/pagination
    public function index(TaskIndexQuery $query): Response
    {
        return Inertia::render('Task/Index', [
            'tasks'   => $query->paginate(15),
            'filters' => [
                'statuses'   => TaskStatus::asOptions(),
                'priorities' => Priority::asOptions(),
            ],
        ]);
    }

    // ── STORE ─────────────────────────────────
    // DTO auto-validated, Action handles business logic
    public function store(TaskData $data, TaskCreateAction $action): RedirectResponse
    {
        $task = $action->execute($data);

        return redirect()
            ->route('tasks.show', $task)
            ->with('message', ['type' => 'success', 'text' => 'Task created.']);
    }

    // ── UPDATE ────────────────────────────────
    public function update(
        TaskData $data,
        Task $task,
        TaskUpdateAction $action
    ): RedirectResponse {
        $action->execute($data, $task);

        return redirect()
            ->route('tasks.show', $task)
            ->with('message', ['type' => 'success', 'text' => 'Task updated.']);
    }

    // ── DESTROY ───────────────────────────────
    // Smart redirect logic — clean but no business logic
    public function destroy(Task $task, TaskDeleteAction $action): RedirectResponse
    {
        $action->execute($task);

        $redirectTo = request()->input('redirect_to');
        if (! $redirectTo && url()->previous() === route('tasks.show', $task)) {
            $redirectTo = $task->project_id
                ? route('projects.show', $task->project_id)
                : route('tasks.index');
        }

        return ($redirectTo ? redirect()->to($redirectTo) : redirect()->back())
            ->with('message', ['type' => 'success', 'text' => 'Task deleted.']);
    }

    // ── PRIVATE HELPERS ───────────────────────
    // Form options extraction — keeps controller methods clean
    private function getFormOptions(): array
    {
        return [
            'projects'   => Project::query()->active()->select('id', 'name')->get(),
            'users'      => User::query()->active()->select('id', 'name')->get(),
            'statuses'   => TaskStatus::asOptions(),
            'priorities' => Priority::asOptions(),
        ];
    }
}
```

## Delegation Map

```
Request → Controller → [Who actually does the work]

GET  /tasks          → index()    → TaskIndexQuery (filter, sort, paginate)
POST /tasks          → store()    → TaskData (validate) + TaskCreateAction (create)
PUT  /tasks/{task}   → update()   → TaskData (validate) + TaskUpdateAction (update)
DELETE /tasks/{task} → destroy()  → TaskDeleteAction (delete)

GET  /tasks/kanban   → kanban()   → KanbanData + TaskKanbanQuery (board builder)
PATCH /tasks/{task}/move → move() → TaskMoveData + TaskMoveAction (reorder)

PUT  /tasks/{task}/complete → complete() → TaskCompleteAction
PUT  /tasks/{task}/assign   → assign()   → TaskAssignData + TaskAssignAction
```

## Key Patterns

### 1. Constructor Injection via Method Parameter
Dependencies are injected per-method, not in constructor:

```php
// ✅ Per-method — only resolves what's needed
public function store(TaskData $data, TaskCreateAction $action) { ... }

// ❌ Constructor — resolves everything, including unused ones
public function __construct(
    private TaskCreateAction $create,
    private TaskUpdateAction $update,
    private TaskDeleteAction $delete,
) {}
```

### 2. Flash Message Convention
All responses use consistent flash messages:

```php
->with('message', [
    'type' => 'success',  // success | error | warning | info
    'text' => 'Task created successfully.',
]);
```

### 3. Inertia Shared Data
Global data is shared via `HandleInertiaRequests` middleware:

```php
// HandleInertiaRequests.php — shared to ALL pages
'auth'                 => ['user' => $request->user()],
'flash'                => [...],
'errors'               => [...],
'organizations'        => [...],
'activeOrganizationId' => GetActiveOrganization::getSelected(),
```

---

**Reference files:** `app/Http/Controllers/*.php`
