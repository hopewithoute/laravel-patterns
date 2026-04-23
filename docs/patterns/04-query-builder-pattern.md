# Query Builder Pattern

> **Encapsulated, Reusable API Query Logic**

## Overview

Project ini menggunakan [Spatie Laravel Query Builder](https://spatie.be/docs/laravel-query-builder) yang di-extend ke class terpisah di `app/QueryBuilders/`. Setiap query builder meng-encapsulate **filter, sort, dan include logic** untuk satu resource, sehingga controller tetap bersih dan query logic bisa di-reuse.

## Struktur Direktori

```
app/QueryBuilders/
├── ProjectIndexQuery.php   → List/index projects dengan filter & sort
├── TaskIndexQuery.php      → List/index tasks dengan filter & sort
└── TaskKanbanQuery.php     → Kanban board query dengan column builder
```

## Konvensi Penamaan

```
{Domain}{View}Query.php
```

- `TaskIndexQuery` → Query untuk halaman list/index
- `TaskKanbanQuery` → Query untuk halaman kanban board
- `ProjectIndexQuery` → Query untuk halaman project list

## Implementasi

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

### Project Index Query (dengan Computed Counts)

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

## Pola-Pola Kunci

### 1. Auto-Resolution di Controller
QueryBuilder di-inject langsung ke controller method — Laravel auto-resolve dari DI container:

```php
public function index(TaskIndexQuery $query): Response
{
    return Inertia::render('Task/Index', [
        'tasks' => $query->paginate(15), // Query sudah terfilter!
    ]);
}
```

### 2. Scope Delegation
Filter kompleks di-delegasikan ke Model Scope via `AllowedFilter::scope()`:

```php
// Di QueryBuilder
AllowedFilter::scope('search', 'search'),

// Di Model
public function scopeSearch(Builder $query, ?string $search): Builder
{
    return $query->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"));
}
```

### 3. Selective Eager Loading
Selalu specify field saat eager loading untuk mengurangi payload:

```php
// ✅ Select hanya field yang dibutuhkan
->with(['project:id,name,color', 'assignee:id,name,avatar']);

// ❌ Load semua kolom
->with(['project', 'assignee']);
```

### 4. Computed Aggregates
Gunakan `withCount` dengan conditional untuk dashboard stats:

```php
->withCount(['tasks as total_tasks'])
->withCount(['tasks as completed_tasks' => fn ($q) => $q->where('status', TaskStatus::Done)])
```

### 5. Custom Serializer
Untuk API endpoints (JSON), gunakan custom serializer untuk kontrol penuh atas response shape:

```php
public function serializeTask(Task $task): array { ... }
```

## URL Query String API

Semua filter dan sort diekspos via URL query string:

```
GET /tasks?filter[status]=Todo&filter[priority]=High&sort=-due_date
GET /tasks?filter[search]=Bug&filter[project_id]=uuid-here
GET /projects?filter[is_active]=1&sort=name
```

---

**Referensi file:** `app/QueryBuilders/*.php`
