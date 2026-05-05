# Enum Pattern

> **Type-Safe Constants with Business Logic**

## Overview

This project uses [BenSampo Laravel Enum](https://github.com/BenSampo/laravel-enum) to define **named constants** that have behavior. Enums are not just a list of strings — they encapsulate business rules, UI metadata, and role-based access control.

## Directory Structure

```
app/Enums/
├── Priority.php       → Task priority levels (Low → Urgent)
├── RoleAuth.php       → Role-based authorization (Super Admin → Member)
└── TaskStatus.php     → Task lifecycle states (Todo → Done)
```

## Implementation

### TaskStatus — Lifecycle State Machine

```php
final class TaskStatus extends Enum
{
    public const Todo       = 'Todo';
    public const InProgress = 'In Progress';
    public const Review     = 'Review';
    public const Done       = 'Done';

    // ── UI Metadata ──────────────
    public static function getColor(string $status): string
    {
        return match ($status) {
            self::Todo       => 'gray',
            self::InProgress => 'blue',
            self::Review     => 'yellow',
            self::Done       => 'green',
            default          => 'gray',
        };
    }

    // ── Business Logic ───────────
    public static function openStatuses(): array
    {
        return [self::Todo, self::InProgress, self::Review];
    }

    public static function isCompleted(string $status): bool
    {
        return $status === self::Done;
    }

    // ── UI Dropdown Options ──────
    public static function asOptions(): array
    {
        return [
            ['text' => 'To Do',       'value' => self::Todo],
            ['text' => 'In Progress', 'value' => self::InProgress],
            ['text' => 'Review',      'value' => self::Review],
            ['text' => 'Done',        'value' => self::Done],
        ];
    }
}
```

### Priority — with Sort Weight

```php
final class Priority extends Enum
{
    public const Low    = 'Low';
    public const Medium = 'Medium';
    public const High   = 'High';
    public const Urgent = 'Urgent';

    // ── UI Color ─────────────────
    public static function getColor(string $priority): string
    {
        return match ($priority) {
            self::Low    => 'gray',
            self::Medium => 'blue',
            self::High   => 'orange',
            self::Urgent => 'red',
            default      => 'gray',
        };
    }

    // ── Sort Weight ──────────────
    public static function getWeight(string $priority): int
    {
        return match ($priority) {
            self::Low    => 1,
            self::Medium => 2,
            self::High   => 3,
            self::Urgent => 4,
            default      => 0,
        };
    }
}
```

### RoleAuth — Contextual Authorization

```php
final class RoleAuth extends Enum
{
    public const SuperAdmin = 'Super Admin';
    public const Owner      = 'Owner';
    public const Admin      = 'Admin';
    public const Member     = 'Member';

    // ── Role Classification ──────
    public static function globalRole(): array
    {
        return [self::SuperAdmin];
    }

    public static function contextualRole(): array
    {
        return [self::Owner, self::Admin, self::Member];
    }

    // ── Permission Groups ────────
    public static function canManageProjects(): array
    {
        return [self::Owner, self::Admin];
    }

    public static function canManageMembers(): array
    {
        return [self::Owner, self::Admin];
    }

    // ── Quick Session Checks ─────
    public static function isSuperAdmin(): bool
    {
        return Session::get('roles') === self::SuperAdmin;
    }
}
```

## Key Patterns

### 1. Enum as Model Cast
Model fields are automatically cast to enum:

```php
// In Model
protected $casts = [
    'status'   => TaskStatus::class,
    'priority' => Priority::class,
];
```

### 2. Enum for Validation
Use `getValues()` for dynamic validation:

```php
'status' => ['required', 'in:'.implode(',', TaskStatus::getValues())],
```

### 3. Enum for UI Metadata
Each enum provides `asOptions()` for dropdowns and `getColor()` for badges:

```php
// Controller exposes to frontend
'filters' => [
    'statuses'   => TaskStatus::asOptions(),
    'priorities' => Priority::asOptions(),
],
```

### 4. Enum for Business Rules
Group rules directly in the enum, not in the controller:

```php
// ✅ Business rule in enum
TaskStatus::openStatuses()    // ['Todo', 'In Progress', 'Review']
RoleAuth::canManageProjects() // ['Owner', 'Admin']

// ❌ Hardcoded in controller
->whereIn('status', ['Todo', 'In Progress', 'Review'])
```

### 5. Enum for SQL Queries
Use enum constants in raw SQL for consistency:

```php
DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Done.'" THEN 1 ELSE 0 END) as done'),
```

## Global vs Contextual Roles

```
┌─────────────────────────────────────────────┐
│              RoleAuth Hierarchy              │
├─────────────────────────────────────────────┤
│                                             │
│  Global Roles (not org-bound)               │
│  └── Super Admin  → bypasses all checks     │
│                                             │
│  Contextual Roles (per-organization)        │
│  ├── Owner  → full access in their org      │
│  ├── Admin  → manage project & members      │
│  └── Member → basic CRUD                    │
│                                             │
└─────────────────────────────────────────────┘
```

---

**Reference files:** `app/Enums/*.php`
