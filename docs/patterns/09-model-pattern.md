# Model Pattern

> **Rich Domain Models dengan Scopes, Accessors, dan Business Methods**

## Overview

Model di project ini bukan sekadar "data container". Setiap model meng-encapsulate:

1. **Relationships** — Relasi antar model
2. **Casts** — Auto-casting ke tipe yang tepat (enum, date, boolean)
3. **Accessors** — Computed attributes untuk frontend
4. **Scopes** — Reusable query fragments
5. **Business Methods** — Domain logic yang terikat ke satu record

## Implementasi

### Task Model — Contoh Lengkap

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
        'status_color',     // Auto-include di JSON
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

## Pola-Pola Kunci

### 1. UUID Primary Keys
Semua model menggunakan UUID, bukan auto-increment:

```php
use HasUuids; // Laravel built-in UUID generation
```

> **Kenapa UUID?** — Aman untuk multi-tenant, tidak bisa di-enumerate, safe untuk public URLs.

### 2. Enum Casting
Field status/priority otomatis di-cast ke Enum class:

```php
protected $casts = [
    'status'   => TaskStatus::class,
    'priority' => Priority::class,
];

// Sekarang bisa: $task->status === TaskStatus::Done
// Bukan:         $task->status === 'Done'
```

### 3. Appended Attributes
Computed values otomatis di-include di JSON (dikirim ke frontend):

```php
protected $appends = ['status_color', 'is_overdue'];

// Frontend langsung terima:
// { id: "...", title: "...", status_color: "blue", is_overdue: false }
```

### 4. Chainable Query Scopes
Scope bisa di-chain dan didelegate ke QueryBuilder:

```php
// Chainable di controller/query
Task::open()->byProject($id)->search('bug')->get();

// Delegate ke Spatie QueryBuilder
AllowedFilter::scope('search', 'search'),
AllowedFilter::scope('overdue', 'overdue'),
```

### 5. Business Methods di Model
Operasi yang terikat ke satu record diletakkan di model:

```php
// ✅ Di Model — operasi pada satu record
$task->markAsCompleted();

// ✅ Di Action — operasi yang melibatkan external concerns
$action->execute($task); // Mungkin trigger notifikasi, logging, dll
```

### 6. Selective Eager Loading
Selalu specify kolom saat eager loading:

```php
$this->belongsTo(User::class, 'assigned_to');

// Di query:
->with(['project:id,name,color', 'assignee:id,name,avatar'])
```

### 7. Route Key Name Override
Untuk model yang perlu slug di URL:

```php
// Organization model
public function getRouteKeyName(): string
{
    return 'slug'; // /workspace/acme-corp instead of /workspace/uuid
}
```

---

**Referensi file:** `app/Models/*.php`
