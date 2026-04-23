# Performance Indexing Pattern

> **Query-Driven Index Design dengan Documented Access Patterns**

## Overview

Migration untuk index di project ini **bukan template generic**. Setiap index didokumentasikan dengan **access pattern** yang jelas — query mana yang akan menggunakan index tersebut.

## Implementasi

```php
/**
 * Add performance indexes for tasks table.
 *
 * Access patterns:
 * - Kanban board: WHERE organization_id = ? AND due_date BETWEEN ? AND ?
 * - Recent tasks: WHERE organization_id = ? ORDER BY created_at DESC
 * - Upcoming deadlines: WHERE organization_id = ? AND due_date BETWEEN ? AND ? AND status != 'Done'
 * - Overdue: WHERE organization_id = ? AND due_date < ? AND status != 'Done'
 * - Project task counts: WHERE project_id = ? AND status = ?
 * - Default sort: ORDER BY priority DESC, due_date ASC
 * - Kanban sort: ORDER BY sort_order ASC, priority DESC, title ASC
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Kanban board queries
            $table->index(['organization_id', 'due_date'], 'tasks_org_due_date_idx');

            // Recent tasks
            $table->index(['organization_id', 'created_at'], 'tasks_org_created_at_idx');

            // Upcoming deadlines & overdue queries
            $table->index(['organization_id', 'status', 'due_date'], 'tasks_org_status_due_idx');

            // Project task counts
            $table->index(['project_id', 'status'], 'tasks_project_status_idx');

            // Kanban ordering
            $table->index(['organization_id', 'sort_order'], 'tasks_org_sort_order_idx');

            // Priority filtering
            $table->index(['organization_id', 'priority'], 'tasks_org_priority_idx');

            // Assigned tasks
            $table->index(['assigned_to', 'due_date'], 'tasks_assignee_due_date_idx');
        });
    }
};
```

## Pola-Pola Kunci

### 1. Composite Index = Left-Most Prefix
Index `['organization_id', 'status', 'due_date']` mendukung:

```sql
WHERE organization_id = ?                              ✅
WHERE organization_id = ? AND status = ?               ✅
WHERE organization_id = ? AND status = ? AND due_date > ? ✅
WHERE status = ? AND due_date > ?                      ❌ (not leftmost)
```

### 2. Organization-First
Semua composite index dimulai dengan `organization_id` karena **setiap query akan di-scope by organization** oleh Global Scope.

### 3. Named Indexes
Index diberi nama deskriptif untuk readability:

```php
$table->index([...], 'tasks_org_due_date_idx');     // ✅ Descriptive
$table->index([...]);                                 // ❌ Auto-generated name
```

### 4. Separate Migration
Index dipisah dari table creation migration:

```
2024_01_01_000004_create_tasks_table.php          → Schema
2026_04_13_145654_add_tasks_performance_indexes.php → Performance
```

> Memudahkan review: perubahan schema vs perubahan performance terpisah.

---

**Referensi file:** `database/migrations/*_performance_indexes.php`
