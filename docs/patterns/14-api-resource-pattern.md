# Pola API Resource

> **Response API Terstandarisasi dengan Eloquent API Resources**

## Gambaran Umum

API Resources mengubah model Eloquent menjadi response JSON dengan struktur yang konsisten. Mereka memisahkan format response API dari model, memungkinkan representasi berbeda untuk endpoint berbeda.

## Struktur Direktori

```
app/Http/Resources/Api/
├── TaskResource.php
├── ProjectResource.php
├── CommentResource.php
├── UserResource.php
└── OrganizationResource.php
```

## Konvensi Penamaan

```
{Model}Resource.php
```

- Terletak di `app/Http/Resources/Api/`
- Satu Resource per Model
- Gunakan `@mixin` untuk dukungan IDE

## Implementasi

### Resource Dasar

```php
<?php

namespace App\Http\Resources\Api;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

#[Mixin(Task::class)]
class TaskResource extends JsonResource
{
    /**
     * @var Task $this
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->toDateString(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

### Resource dengan Relasi Kondisional

```php
#[Mixin(Task::class)]
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            // Hanya sertakan ketika relasi di-load
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
```

### Resource dengan Atribut Komputasi

```php
#[Mixin(Organization::class)]
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'invite_code' => $this->invite_code,
            'is_active' => $this->is_active,
            // Atribut komputasi
            'members_count' => $this->whenCounted('members'),
        ];
    }
}
```

## Penggunaan di Controller

### Resource Tunggal

```php
public function show(Task $task): TaskResource
{
    return new TaskResource($task->load(['project', 'assignee']));
}
```

### Koleksi Resource

```php
public function index(TaskIndexQuery $query): AnonymousResourceCollection
{
    return TaskResource::collection($query->jsonPaginate());
}
```

### dengan Loading Kondisional

```php
public function show(Task $task): TaskResource
{
    $task->loadWhen(
        $request->boolean('with_comments'),
        'comments.user'
    );

    return new TaskResource($task);
}
```

## Pola-Pola Kunci

### 1. `@mixin` untuk Dukungan IDE

Selalu tambahkan `#[Mixin(Model::class)]` untuk autocomplete:

```php
#[Mixin(Task::class)]  // ← IDE tahu $this->title, $this->status, dll.
class TaskResource extends JsonResource
```

### 2. `whenLoaded()` untuk Relasi

Jangan pernah akses relasi yang belum di-load:

```php
// ✅ Aman - return null jika belum di-load
'project' => new ProjectResource($this->whenLoaded('project')),

// ❌ Akan error jika belum di-load
'project' => new ProjectResource($this->project),
```

### 3. Format di Resource, Bukan Model

Simpan formatting API di Resource, bukan di Model accessor:

```php
// ✅ Di Resource
'created_at' => $this->created_at->toIso8601String(),

// ❌ Di Model (mencemari model dengan concern API)
protected function createdAt(): Attribute
{
    return Attribute::get(fn () => $this->created_at->toIso8601String());
}
```

### 4. Atribut Kondisional

Gunakan `when()` untuk inklusi kondisional:

```php
'admin_notes' => $this->when($request->user()->isAdmin(), $this->admin_notes),
```

### 5. Koleksi Resource

Gunakan `collection()` untuk array/list:

```php
return TaskResource::collection($tasks);
// Returns: { data: [...], links: {...}, meta: {...} }
```

## Struktur Response API

### Resource Tunggal
```json
{
    "data": {
        "id": "uuid",
        "title": "Judul Task",
        "status": "Todo",
        "project": { ... }
    }
}
```

### Koleksi (paginated)
```json
{
    "data": [
        { "id": "uuid-1", "title": "Task 1" },
        { "id": "uuid-2", "title": "Task 2" }
    ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 75
    }
}
```

---

**Referensi file:** `app/Http/Resources/Api/*.php`
