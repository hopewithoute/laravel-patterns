# Action Pattern

> **Single-Responsibility Business Logic**

## Overview

Action adalah class yang **hanya bertanggung jawab untuk satu operasi bisnis**. Setiap action class memiliki satu method `execute()` yang menjalankan satu tugas spesifik. Pattern ini memisahkan business logic dari controller, sehingga controller tetap **thin** dan hanya bertanggung jawab untuk routing dan response.

## Mengapa Menggunakan Action?

| Masalah (Fat Controller)                   | Solusi (Action Pattern)                              |
|--------------------------------------------|------------------------------------------------------|
| Controller berisi ratusan baris logic      | Setiap logic terisolasi di class sendiri              |
| Sulit di-unit test karena tightly coupled  | Mudah di-test karena single responsibility            |
| Logic duplikasi di berbagai controller     | Action bisa di-reuse di controller, command, job, dll |
| Sulit dibaca dan di-maintain               | Satu file = satu operasi = mudah dipahami             |

## Struktur Direktori

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

## Konvensi Penamaan

```
{Domain}{Verb}Action.php
```

**Contoh:**
- `TaskCreateAction` → Membuat task baru
- `TaskMoveAction` → Memindahkan task (drag-and-drop)
- `AuthLoginAction` → Proses login
- `WorkspaceCreateAction` → Membuat workspace + setup awal

## Implementasi

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

### Complex Action (dengan Transaction & Multi-Step)

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

## Pola-Pola Kunci

### 1. `readonly class`
Semua Action menggunakan `readonly class` untuk menjamin **immutability** — tidak ada state yang berubah setelah construction.

### 2. Constructor Injection
Dependencies di-inject melalui constructor, bukan di-resolve manual:

```php
public function __construct(
    private readonly Task $model,
) {}
```

### 3. `DB::transaction()`
Semua operasi yang melibatkan multiple database writes dibungkus dalam transaction:

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

### 4. DTO sebagai Input
Action selalu menerima **Data Transfer Object** (bukan raw array) sebagai parameter utama:

```php
public function execute(TaskData $data): Task     // ✅ Typed DTO
public function execute(array $data): Task         // ❌ Raw array
```

## Penggunaan di Controller

Controller hanya menjadi **orchestrator** — menerima request, memanggil action, mengembalikan response:

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

## Reuse Across Web dan API

Action dirancang untuk **digunakan kembali** di berbagai konteks:

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

**Action yang sama, tipe response berbeda.**

### API Partial Updates

Untuk partial updates, DTO menggunakan aturan `sometimes|required`:

```php
// Di DTO rules()
'title' => ['sometimes', 'required', 'string', 'max:255'],
'status' => ['sometimes', 'required', new Enum(TaskStatus::class)],
```

Action menggunakan `array_filter` untuk hanya update field yang tidak null:

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

## Kapan Membuat Action Baru?

- ✅ **Operasi CRUD** — Create, Update, Delete untuk setiap domain
- ✅ **Business logic** — Assign task, complete task, invite user
- ✅ **Multi-step operations** — Create workspace + assign role + send invites
- ❌ **Simple queries** — Gunakan QueryBuilder untuk read operations
- ❌ **Aggregation/statistics** — Gunakan Service class

---

**Referensi file:** `app/Actions/*.php`
