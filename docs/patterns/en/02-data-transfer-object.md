# Data Transfer Object (DTO) Pattern

> **Type-Safe Request Validation & Data Transformation**

## Overview

This project uses [Spatie Laravel Data](https://spatie.be/docs/laravel-data) as the DTO layer. DTOs are responsible for:

1. **Validation** — Replacing Form Request with rules defined in the DTO
2. **Type Safety** — Typed properties ensure data is always consistent
3. **Transformation** — The `toModelData()` method separates request shape from database shape
4. **Auto-Resolution** — Laravel automatically resolves DTOs from requests in controller methods

## Directory Structure

```
app/Data/
├── CommentData.php
├── ForgotPasswordData.php
├── KanbanData.php
├── LoginData.php
├── PasswordUpdateData.php
├── ProfileUpdateData.php
├── ProjectData.php
├── RegisterData.php
├── ResetPasswordData.php
├── TaskAssignData.php
├── TaskData.php
├── TaskMoveData.php
├── UserInviteData.php
├── WorkspaceCreateData.php
└── WorkspaceSetData.php
```

## Naming Convention

```
{Domain}Data.php          → Main DTO (CRUD)
{Domain}{Operation}Data.php → DTO for specific operations
```

**Examples:**
- `TaskData` → General CRUD for Task
- `TaskMoveData` → Specific to drag-and-drop operations
- `TaskAssignData` → Specific to user assignment operations

## Implementation

### Full DTO with Validation, Messages, and Transformation

```php
<?php

namespace App\Data;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Task.
 * Handles validation and transformation for task data.
 */
class TaskData extends Data
{
    public function __construct(
        public ?string $id,
        public ?string $organization_id,
        public string $project_id,
        public ?string $assigned_to,
        public string $title,
        public ?string $description,
        public string $status,
        public string $priority,
        public ?string $due_date,
        public ?string $completed_at,
    ) {}

    // ── Validation Rules ──────────────────────────────────
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'id'              => ['nullable', 'string', 'uuid', 'exists:tasks,id'],
            'organization_id' => ['nullable', 'string', 'uuid', 'exists:organizations,id'],
            'project_id'      => ['required', 'string', 'uuid', 'exists:projects,id'],
            'assigned_to'     => ['nullable', 'string', 'uuid', 'exists:users,id'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'status'          => ['required', 'string', 'in:'.implode(',', TaskStatus::getValues())],
            'priority'        => ['required', 'string', 'in:'.implode(',', Priority::getValues())],
            'due_date'        => ['nullable', 'date'],
            'completed_at'    => ['nullable', 'date'],
        ];
    }

    // ── Custom Error Messages ─────────────────────────────
    public static function messages(...$args): array
    {
        return [
            'title.required'    => 'Task title is required.',
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Selected project does not exist.',
            'status.in'         => 'Invalid task status.',
            'priority.in'       => 'Invalid priority level.',
        ];
    }

    // ── Authorization ─────────────────────────────────────
    public static function authorize(): bool
    {
        return true;
    }

    // ── Data Transformation ───────────────────────────────
    /**
     * Prepare data for storage.
     * Separates request fields from database fields.
     */
    public function toModelData(): array
    {
        return [
            'project_id'  => $this->project_id,
            'assigned_to' => $this->assigned_to,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'due_date'    => $this->due_date,
        ];
    }
}
```

## Why DTOs Instead of Form Requests?

| Aspect             | Form Request          | DTO (Spatie Data)               |
|--------------------|-----------------------|---------------------------------|
| Validation         | ✅ Yes                | ✅ Yes                          |
| Type-safe          | ❌ Returns array      | ✅ Typed properties             |
| Transformation     | ❌ Manual             | ✅ Built-in `toModelData()`     |
| Reusable           | ❌ HTTP-only          | ✅ Can be used in Jobs, Commands|
| IDE autocomplete   | ❌ `$request['key']`  | ✅ `$data->title`               |
| Serialization      | ❌ None               | ✅ `toArray()`, `toJson()`      |

## Key Patterns

### 1. Enum-based Validation
Validate enum values directly from the Enum class, not hardcoded strings:

```php
'status' => ['required', 'string', 'in:'.implode(',', TaskStatus::getValues())],
```

> If the enum changes, validation automatically updates.

### 2. `toModelData()` Transformation
Separates request fields from database fields:

```php
// Request has `id` and `organization_id`, but they don't go into create
public function toModelData(): array
{
    return [
        'title'       => $this->title,
        'description' => $this->description,
        // `id` and `organization_id` are not included
    ];
}
```

### 3. Auto-Resolution in Controller
Laravel automatically validates and populates DTOs from requests:

```php
// Laravel automatically:
// 1. Takes data from request
// 2. Validates using rules()
// 3. Populates DTO properties
// 4. Injects into controller parameter
public function store(TaskData $data, TaskCreateAction $action): RedirectResponse
{
    $task = $action->execute($data);
    // ...
}
```

### 4. Operation-Specific DTOs
Create separate DTOs for operations with different payloads:

```php
// Assignment operation only needs 1 field
class TaskAssignData extends Data
{
    public function __construct(
        public string $assigned_to,
    ) {}
}

// Move operation needs different fields
class TaskMoveData extends Data
{
    public function __construct(
        public ?string $due_date,
        public int $sort_order,
    ) {}
}
```

---

**Reference files:** `app/Data/*.php`
