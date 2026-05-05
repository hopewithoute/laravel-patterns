# Model Pattern

> **Rich Domain Models with Scopes, Accessors, and Business Methods**

## Overview

Models in this project are not just "data containers". Each model encapsulates:

1. **Relationships** — Relations between models
2. **Casts** — Auto-casting to appropriate types (enum, date, boolean)
3. **Accessors** — Computed attributes for frontend
4. **Scopes** — Reusable query fragments
5. **Business Methods** — Domain logic bound to a single record

## Implementation

### Task Model — Complete Example

```php
class Task extends Model
{
    use HasFactory, HasOrganization, HasUuids;

    // ── Mass-Assignment Protection ─────────────
    protected $fillable = [
        'organization_id', 'project_id', 'assigned_to',
        'title', 'description', 'status', 'priority',
        'due_date', 'sort_order', 'completed_at',
    ];

    // ── Type Casting ───────────────────────────
    protected $casts = [
        'status'       => TaskStatus::class,   // String → Enum
        'priority'     => Priority::class,     // String → Enum
        'due_date'     => 'date',              // String → Carbon
        'sort_order'   => 'integer',
        'completed_at' => 'datetime',
    ];

    // ── Computed Attributes ────────────────────
    protected $appends = [
        'status_color',     // Auto-included in JSON
        'priority_color',
        'is_overdue',
    ];

    // ── Relationships ──────────────────────────
    public function project(): BelongsTo { ... }
    public function assignee(): BelongsTo { ... }
    public function comments(): HasMany { ... }

    // ── Accessors ──────────────────────────────
    public function getStatusColorAttribute(): string
    {
        return TaskStatus::getColor($this->status);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_date || $this->status === TaskStatus::Done) {
            return false;
        }
        return $this->due_date->isPast();
    }

    // ── Query Scopes ───────────────────────────
    public function scopeByStatus(Builder $query, string $status): Builder { ... }
    public function scopeOverdue(Builder $query): Builder { ... }
    public function scopeOpen(Builder $query): Builder { ... }
    public function scopeSearch(Builder $query, ?string $search): Builder { ... }

    // ── Business Methods ───────────────────────
    public function markAsCompleted(): void
    {
        $this->update([
            'status'       => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }

    public function markAsOpen(string $status = TaskStatus::Todo): void
    {
        $this->update([
            'status'       => $status,
            'completed_at' => null,
        ]);
    }
}
```

### Project Model — Computed Aggregates

```php
class Project extends Model
{
    use HasFactory, HasOrganization, HasUuids;

    protected $appends = [
        'tasks_count',
        'completed_tasks_count',
        'completion_percentage',
    ];

    public function getTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public function getCompletionPercentageAttribute(): int
    {
        $total = $this->tasks_count;
        if ($total === 0) return 0;
        return (int) round(($this->completed_tasks_count / $total) * 100);
    }
}
```

## Key Patterns

### 1. UUID Primary Keys
All models use UUIDs, not auto-increment:

```php
use HasUuids; // Laravel built-in UUID generation
```

> **Why UUIDs?** — Safe for multi-tenant, cannot be enumerated, safe for public URLs.

### 2. Enum Casting
Status/priority fields are automatically cast to Enum classes:

```php
protected $casts = [
    'status'   => TaskStatus::class,
    'priority' => Priority::class,
];

// Now you can: $task->status === TaskStatus::Done
// Not:         $task->status === 'Done'
```

### 3. Appended Attributes
Computed values are automatically included in JSON (sent to frontend):

```php
protected $appends = ['status_color', 'is_overdue'];

// Frontend receives directly:
// { id: "...", title: "...", status_color: "blue", is_overdue: false }
```

### 4. Chainable Query Scopes
Scopes can be chained and delegated to QueryBuilder:

```php
// Chainable in controller/query
Task::open()->byProject($id)->search('bug')->get();

// Delegate to Spatie QueryBuilder
AllowedFilter::scope('search', 'search'),
AllowedFilter::scope('overdue', 'overdue'),
```

### 5. Business Methods in Model
Operations bound to a single record are placed in the model:

```php
// ✅ In Model — operation on a single record
$task->markAsCompleted();

// ✅ In Action — operation involving external concerns
$action->execute($task); // May trigger notifications, logging, etc.
```

### 6. Selective Eager Loading
Always specify columns when eager loading:

```php
$this->belongsTo(User::class, 'assigned_to');

// In query:
->with(['project:id,name,color', 'assignee:id,name,avatar'])
```

### 7. Route Key Name Override
For models that need slugs in URLs:

```php
// Organization model
public function getRouteKeyName(): string
{
    return 'slug'; // /workspace/acme-corp instead of /workspace/uuid
}
```

---

**Reference files:** `app/Models/*.php`
