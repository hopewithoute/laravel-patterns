# Action Pattern

> **Single-Responsibility Business Logic**

## Overview

Actions are classes that are **only responsible for a single business operation**. Each action class has a single `execute()` method that performs one specific task. This pattern separates business logic from controllers, keeping controllers **thin** and only responsible for routing and response handling.

## Why Use Actions?

| Problem (Fat Controller)                    | Solution (Action Pattern)                              |
|---------------------------------------------|--------------------------------------------------------|
| Controller contains hundreds of lines logic | Each logic is isolated in its own class                |
| Hard to unit test due to tight coupling     | Easy to test due to single responsibility              |
| Logic duplication across controllers        | Actions can be reused in controllers, commands, jobs   |
| Hard to read and maintain                   | One file = one operation = easy to understand          |

## Directory Structure

```
app/Actions/
├── AuthForgotPasswordAction.php
├── AuthLoginAction.php
├── AuthRegisterAction.php
├── AuthResetPasswordAction.php
├── CommentCreateAction.php
├── CommentDeleteAction.php
├── OrganizationRegenerateInviteCodeAction.php
├── PasswordUpdateAction.php
├── ProfileUpdateAction.php
├── ProjectCreateAction.php
├── ProjectDeleteAction.php
├── ProjectUpdateAction.php
├── TaskAssignAction.php
├── TaskCompleteAction.php
├── TaskCreateAction.php
├── TaskDeleteAction.php
├── TaskMoveAction.php
├── TaskStatusUpdateAction.php
├── TaskUpdateAction.php
├── UserInviteAction.php
├── UserRemoveAction.php
├── WorkspaceCreateAction.php
└── WorkspaceSetAction.php
```

## Naming Convention

```
{Domain}{Verb}Action.php
```

**Examples:**
- `TaskCreateAction` → Creates a new task
- `TaskMoveAction` → Moves a task (drag-and-drop)
- `AuthLoginAction` → Handles login process
- `WorkspaceCreateAction` → Creates a workspace + initial setup

## Implementation

### Basic Action

```php
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
            return $this->model->create(array_merge(
                $data->toModelData(),
                [
                    'organization_id' => $data->organization_id ?? session('organization_id'),
                ]
            ));
        });
    }
}
```

### Complex Action (with Transaction & Multi-Step)

```php
<?php

namespace App\Actions;

use App\Data\WorkspaceCreateData;
use App\Models\Organization;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new workspace and invite initial members.
 */
readonly class WorkspaceCreateAction
{
    /**
     * @return array{organization: Organization, invited_count: int}
     */
    public function execute(WorkspaceCreateData $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user) {
            // Step 1: Create organization
            $organization = Organization::create([
                'name'        => $data->name,
                'description' => $data->description,
                'is_active'   => true,
            ]);

            // Step 2: Add creator as admin
            $organization->addMember($user, 'admin');

            // Step 3: Process invite emails
            $invitedCount = 0;
            if (! empty($data->invite_emails)) {
                // ... invite logic
            }

            // Step 4: Set as active workspace
            GetActiveOrganization::setWithoutValidation($organization->id);

            return [
                'organization' => $organization,
                'invited_count' => $invitedCount,
            ];
        });
    }
}
```

## Key Patterns

### 1. `readonly class`
All Actions use `readonly class` to ensure **immutability** — no state changes after construction.

### 2. Constructor Injection
Dependencies are injected through the constructor, not resolved manually:

```php
public function __construct(
    private readonly Task $model,
) {}
```

### 3. `DB::transaction()`
All operations involving multiple database writes are wrapped in a transaction:

```php
return DB::transaction(function () use ($task, $data) {
    Task::whereDate('due_date', $data->due_date)
        ->where('id', '!=', $task->getKey())
        ->where('sort_order', '>=', $data->sort_order)
        ->increment('sort_order');

    $task->update([...]);

    return $task->fresh();
});
```

### 4. DTO as Input
Actions always accept a **Data Transfer Object** (not raw array) as the primary parameter:

```php
public function execute(TaskData $data): Task     // ✅ Typed DTO
public function execute(array $data): Task         // ❌ Raw array
```

## Usage in Controller

Controllers only act as **orchestrators** — receiving requests, calling actions, and returning responses:

```php
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
```

## Reuse Across Web and API

Actions are designed to be **reused** across different contexts:

### Web Controller (Inertia)

```php
public function store(TaskData $data, TaskCreateAction $action): RedirectResponse
{
    $task = $action->execute($data);

    return redirect()
        ->route('tasks.show', $task)
        ->with('success', 'Task created successfully.');
}
```

### API Controller (Sanctum)

```php
public function store(TaskData $data, TaskCreateAction $action): TaskResource
{
    $task = $action->execute($data);

    return new TaskResource($task);
}
```

**Same Action, different response type.**

### API Partial Updates

For partial updates, DTOs use `sometimes|required` rules:

```php
// In DTO rules()
'title' => ['sometimes', 'required', 'string', 'max:255'],
'status' => ['sometimes', 'required', new Enum(TaskStatus::class)],
```

Action uses `array_filter` to only update non-null fields:

```php
public function execute(TaskData $data, Task $task): Task
{
    return DB::transaction(function () use ($data, $task) {
        $task->update(array_filter(
            $data->toModelData(),
            fn ($value) => $value !== null
        ));

        return $task->fresh();
    });
}
```

## When to Create a New Action?

- ✅ **CRUD operations** — Create, Update, Delete for each domain
- ✅ **Business logic** — Assign task, complete task, invite user
- ✅ **Multi-step operations** — Create workspace + assign role + send invites
- ❌ **Simple queries** — Use QueryBuilder for read operations
- ❌ **Aggregation/statistics** — Use Service class

---

**Reference files:** `app/Actions/*.php`
