# Enum Pattern

> **Type-Safe Constants dengan Business Logic**

## Overview

Project ini menggunakan [BenSampo Laravel Enum](https://github.com/BenSampo/laravel-enum) untuk mendefinisikan **named constants** yang memiliki behavior. Enum bukan sekadar daftar string — mereka meng-encapsulate business rules, UI metadata, dan role-based access control.

## Struktur Direktori

```
app/Enums/
├── Priority.php       → Task priority levels (Low → Urgent)
├── RoleAuth.php       → Role-based authorization (Super Admin → Member)
└── TaskStatus.php     → Task lifecycle states (Todo → Done)
```

## Implementasi

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

### Priority — dengan Sort Weight

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

## Pola-Pola Kunci

### 1. Enum sebagai Model Cast
Field model otomatis di-cast ke enum:

```php
// Di Model
protected $casts = [
    'status'   => TaskStatus::class,
    'priority' => Priority::class,
];
```

### 2. Enum untuk Validasi
Gunakan `getValues()` untuk validasi dinamis:

```php
'status' => ['required', 'in:'.implode(',', TaskStatus::getValues())],
```

### 3. Enum untuk UI Metadata
Setiap enum menyediakan `asOptions()` untuk dropdown dan `getColor()` untuk badge:

```php
// Controller meng-expose ke frontend
'filters' => [
    'statuses'   => TaskStatus::asOptions(),
    'priorities' => Priority::asOptions(),
],
```

### 4. Enum untuk Business Rules
Group rules langsung di enum, bukan di controller:

```php
// ✅ Business rule di enum
TaskStatus::openStatuses()    // ['Todo', 'In Progress', 'Review']
RoleAuth::canManageProjects() // ['Owner', 'Admin']

// ❌ Hardcode di controller
->whereIn('status', ['Todo', 'In Progress', 'Review'])
```

### 5. Enum untuk Query SQL
Gunakan enum constant di raw SQL untuk consistency:

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
│  └── Super Admin  → bypass semua check      │
│                                             │
│  Contextual Roles (per-organization)        │
│  ├── Owner  → full access di org-nya        │
│  ├── Admin  → manage project & members      │
│  └── Member → basic CRUD                    │
│                                             │
└─────────────────────────────────────────────┘
```

---

**Referensi file:** `app/Enums/*.php`
