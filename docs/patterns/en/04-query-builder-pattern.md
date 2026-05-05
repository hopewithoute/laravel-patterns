# Query Builder Pattern

> **Encapsulated, Reusable API Query Logic**

## Overview

This project uses [Spatie Laravel Query Builder](https://spatie.be/docs/laravel-query-builder) extended into separate classes in `app/QueryBuilders/`. Each query builder encapsulates **filter, sort, and include logic** for a single resource, keeping controllers clean and query logic reusable.

## Directory Structure

```
app/QueryBuilders/
├── ProjectIndexQuery.php   → List/index projects with filter & sort
├── TaskIndexQuery.php      → List/index tasks with filter & sort
└── TaskKanbanQuery.php     → Kanban board query with column builder
```

## Naming Convention

```
{Domain}{View}Query.php
```

- `TaskIndexQuery` → Query for list/index page
- `TaskKanbanQuery` → Query for kanban board page
- `ProjectIndexQuery` → Query for project list page

## Implementation

### Index Query (Filter + Sort + Pagination)

```php
<?php

namespace App\QueryBuilders;

use App\Models\Task;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Task::query()
            ->with(['project:id,name,color', 'assignee:id,name,avatar']);

        parent::__construct($query, $request);

        $this
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('assigned_to'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::scope('search', 'search'),   // Delegates to model scope
                AllowedFilter::scope('overdue', 'overdue'),
                AllowedFilter::scope('open', 'open'),
            )
            ->allowedSorts(
                'title', 'status', 'priority',
                'due_date', 'created_at', 'updated_at',
            )
            ->defaultSort('-priority', 'due_date');
    }
}
```

### Specialized Query (Kanban Board)

```php
class TaskKanbanQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Task::query()
            ->with(['project:id,name,color', 'assignee:id,name,avatar']);

        parent::__construct($query, $request);
        // ... filters
    }

    /**
     * Build the full kanban board data structure.
     */
    public function getBoard(string $startDate, string $endDate): array
    {
        $tasks = $this
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('due_date', [$startDate, $endDate])
                    ->orWhereNull('due_date');
            })
            ->orderBy('sort_order')
            ->orderByRaw($this->priorityCaseExpression().' desc')
            ->orderBy('title')
            ->get();

        $columns = $this->buildColumns($startDate, $endDate);
        // ... group tasks by column

        return [
            'meta'            => [...],
            'columns'         => $columns,
            'tasks_by_column' => $tasksByColumn,
        ];
    }

    /**
     * Serialize task for kanban API response.
     * Controls the exact data shape sent to frontend.
     */
    public function serializeTask(Task $task): array
    {
        return [
            'id'         => $task->id,
            'title'      => $task->title,
            'status'     => $task->status,
            'priority'   => $task->priority,
            'due_date'   => $task->due_date?->toDateString(),
            'sort_order' => $task->sort_order,
            'is_overdue' => $task->is_overdue,
            'project'    => $task->project ? [...] : null,
            'assignee'   => $task->assignee ? [...] : null,
        ];
    }

    /**
     * Priority ordering via CASE expression.
     */
    private function priorityCaseExpression(): string
    {
        return sprintf(
            "case priority when '%s' then 4 when '%s' then 3 when '%s' then 2 when '%s' then 1 else 0 end",
            Priority::Urgent,
            Priority::High,
            Priority::Medium,
            Priority::Low,
        );
    }
}
```

### Project Index Query (with Computed Counts)

```php
class ProjectIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Project::query()
            ->withCount(['tasks as total_tasks'])
            ->withCount(['tasks as completed_tasks' => function ($q) {
                $q->where('status', TaskStatus::Done);
            }]);

        parent::__construct($query, $request);

        $this
            ->allowedFilters(
                AllowedFilter::exact('is_active'),
                AllowedFilter::partial('name'),
                AllowedFilter::scope('search', 'search'),
            )
            ->allowedSorts(
                'name', 'created_at', 'updated_at',
                AllowedSort::field('tasks_count', 'total_tasks'),
            )
            ->defaultSort('-created_at');
    }
}
```

## Key Patterns

### 1. Auto-Resolution in Controller
QueryBuilder is injected directly into controller methods — Laravel auto-resolves from the DI container:

```php
public function index(TaskIndexQuery $query): Response
{
    return Inertia::render('Task/Index', [
        'tasks' => $query->paginate(15), // Query is already filtered!
    ]);
}
```

### 2. Scope Delegation
Complex filters are delegated to Model Scopes via `AllowedFilter::scope()`:

```php
// In QueryBuilder
AllowedFilter::scope('search', 'search'),

// In Model
public function scopeSearch(Builder $query, ?string $search): Builder
{
    return $query->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"));
}
```

### 3. Selective Eager Loading
Always specify fields when eager loading to reduce payload:

```php
// ✅ Select only needed fields
->with(['project:id,name,color', 'assignee:id,name,avatar']);

// ❌ Load all columns
->with(['project', 'assignee']);
```

### 4. Computed Aggregates
Use `withCount` with conditionals for dashboard stats:

```php
->withCount(['tasks as total_tasks'])
->withCount(['tasks as completed_tasks' => fn ($q) => $q->where('status', TaskStatus::Done)])
```

### 5. Custom Serializer
For API endpoints (JSON), use a custom serializer for full control over response shape:

```php
public function serializeTask(Task $task): array { ... }
```

## URL Query String API

All filters and sorts are exposed via URL query string:

```
GET /tasks?filter[status]=Todo&filter[priority]=High&sort=-due_date
GET /tasks?filter[search]=Bug&filter[project_id]=uuid-here
GET /projects?filter[is_active]=1&sort=name
```

## API Usage with jsonPaginate()

API controllers use QueryBuilder injection with `jsonPaginate()` from `spatie/laravel-json-api-paginate`:

```php
// Controller
public function index(TaskIndexQuery $query): AnonymousResourceCollection
{
    return TaskResource::collection($query->jsonPaginate());
}
```

**Package Required:** `composer require spatie/laravel-json-api-paginate`

**Response Format:**
```json
{
    "data": [...],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

## API-Specific QueryBuilders

Create separate QueryBuilders when API needs differ from Web:

```
app/QueryBuilders/
├── TaskIndexQuery.php              ← Web (Inertia pagination)
├── ProjectIndexQuery.php           ← Web
└── Api/
    ├── TaskIndexQuery.php          ← API (jsonPaginate, different eager loading)
    └── ProjectIndexQuery.php       ← API
```

**When to create API-specific QueryBuilder:**
- Eager loading differs (API needs more/fewer relations)
- Pagination differs (`jsonPaginate()` vs `paginate()`)
- Filter/sort differs

---

**Reference files:** `app/QueryBuilders/*.php`, `app/Http/Controllers/Api/*.php`
