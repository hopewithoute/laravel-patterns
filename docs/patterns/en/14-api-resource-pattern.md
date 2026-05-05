# API Resource Pattern

> **Standardized API Responses with Eloquent API Resources**

## Overview

API Resources transform Eloquent models into JSON responses with consistent structure. They separate the API response format from the model, allowing different representations for different endpoints.

## Directory Structure

```
app/Http/Resources/Api/
├── TaskResource.php
├── ProjectResource.php
├── CommentResource.php
├── UserResource.php
└── OrganizationResource.php
```

## Naming Convention

```
{Model}Resource.php
```

- Located in `app/Http/Resources/Api/`
- One Resource per Model
- Use `@mixin` for IDE support

## Implementation

### Basic Resource

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

### Resource with Conditional Relationships

```php
#[Mixin(Task::class)]
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            // Only include when relationship is loaded
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
```

### Resource with Computed Attributes

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
            // Computed attribute
            'members_count' => $this->whenCounted('members'),
        ];
    }
}
```

## Usage in Controllers

### Single Resource

```php
public function show(Task $task): TaskResource
{
    return new TaskResource($task->load(['project', 'assignee']));
}
```

### Resource Collection

```php
public function index(TaskIndexQuery $query): AnonymousResourceCollection
{
    return TaskResource::collection($query->jsonPaginate());
}
```

### With Conditional Loading

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

## Key Patterns

### 1. `@mixin` for IDE Support

Always add `#[Mixin(Model::class)]` for autocomplete:

```php
#[Mixin(Task::class)]  // ← IDE knows $this->title, $this->status, etc.
class TaskResource extends JsonResource
```

### 2. `whenLoaded()` for Relationships

Never access unloaded relationships:

```php
// ✅ Safe - returns null if not loaded
'project' => new ProjectResource($this->whenLoaded('project')),

// ❌ Will throw error if not loaded
'project' => new ProjectResource($this->project),
```

### 3. Format in Resource, Not Model

Keep API formatting in Resource, not Model accessors:

```php
// ✅ In Resource
'created_at' => $this->created_at->toIso8601String(),

// ❌ In Model (pollutes model with API concerns)
protected function createdAt(): Attribute
{
    return Attribute::get(fn () => $this->created_at->toIso8601String());
}
```

### 4. Conditional Attributes

Use `when()` for conditional inclusion:

```php
'admin_notes' => $this->when($request->user()->isAdmin(), $this->admin_notes),
```

### 5. Resource Collections

Use `collection()` for arrays/lists:

```php
return TaskResource::collection($tasks);
// Returns: { data: [...], links: {...}, meta: {...} }
```

## API Response Structure

### Single Resource
```json
{
    "data": {
        "id": "uuid",
        "title": "Task Title",
        "status": "Todo",
        "project": { ... }
    }
}
```

### Collection (paginated)
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

**Reference files:** `app/Http/Resources/Api/*.php`
