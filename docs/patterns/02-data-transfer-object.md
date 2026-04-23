# Data Transfer Object (DTO) Pattern

> **Type-Safe Request Validation & Data Transformation**

## Overview

Project ini menggunakan [Spatie Laravel Data](https://spatie.be/docs/laravel-data) sebagai DTO layer. DTO bertanggung jawab untuk:

1. **Validasi** — Menggantikan Form Request dengan rules di DTO
2. **Type Safety** — Typed properties memastikan data selalu konsisten
3. **Transformation** — Method `toModelData()` memisahkan request shape dari database shape
4. **Auto-Resolution** — Laravel secara otomatis me-resolve DTO dari request di controller method

## Struktur Direktori

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

## Konvensi Penamaan

```
{Domain}Data.php          → DTO utama (CRUD)
{Domain}{Operation}Data.php → DTO untuk operasi spesifik
```

**Contoh:**
- `TaskData` → CRUD umum untuk Task
- `TaskMoveData` → Khusus operasi drag-and-drop
- `TaskAssignData` → Khusus operasi assign user

## Implementasi

### Full DTO dengan Validation, Messages, dan Transformation

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
     * Memisahkan field yang masuk ke database dari field request.
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

## Kenapa DTO, Bukan Form Request?

| Aspek              | Form Request          | DTO (Spatie Data)               |
|--------------------|-----------------------|---------------------------------|
| Validasi           | ✅ Ya                 | ✅ Ya                           |
| Type-safe          | ❌ Returns array      | ✅ Typed properties             |
| Transformation     | ❌ Manual             | ✅ Built-in `toModelData()`     |
| Reusable           | ❌ HTTP-only          | ✅ Bisa dipakai di Job, Command |
| IDE autocomplete   | ❌ `$request['key']`  | ✅ `$data->title`               |
| Serialization      | ❌ Tidak ada          | ✅ `toArray()`, `toJson()`      |

## Pola-Pola Kunci

### 1. Enum-based Validation
Validasi enum value langsung dari Enum class, bukan hardcode string:

```php
'status' => ['required', 'string', 'in:'.implode(',', TaskStatus::getValues())],
```

> Jika enum berubah, validasi otomatis ikut berubah.

### 2. `toModelData()` Transformation
Memisahkan field request dari field yang masuk ke database:

```php
// Request punya `id` dan `organization_id`, tapi tidak masuk ke create
public function toModelData(): array
{
    return [
        'title'       => $this->title,
        'description' => $this->description,
        // `id` dan `organization_id` tidak di-include
    ];
}
```

### 3. Auto-Resolution di Controller
Laravel otomatis mem-validate dan mem-populate DTO dari request:

```php
// Laravel secara otomatis:
// 1. Ambil data dari request
// 2. Validate menggunakan rules()
// 3. Populate DTO properties
// 4. Inject ke parameter controller
public function store(TaskData $data, TaskCreateAction $action): RedirectResponse
{
    $task = $action->execute($data);
    // ...
}
```

### 4. Operation-Specific DTOs
Buat DTO terpisah untuk operasi yang punya payload berbeda:

```php
// Operasi assign hanya butuh 1 field
class TaskAssignData extends Data
{
    public function __construct(
        public string $assigned_to,
    ) {}
}

// Operasi move butuh field yang berbeda
class TaskMoveData extends Data
{
    public function __construct(
        public ?string $due_date,
        public int $sort_order,
    ) {}
}
```

---

**Referensi file:** `app/Data/*.php`
