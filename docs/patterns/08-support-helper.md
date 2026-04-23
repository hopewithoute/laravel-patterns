# Support Helper Pattern

> **Utility Classes untuk Cross-Cutting Concerns**

## Overview

`app/Supports/` berisi **stateless utility classes** yang menyediakan helper functions untuk cross-cutting concerns — hal-hal yang dibutuhkan di banyak tempat tapi bukan business logic domain.

## Struktur Direktori

```
app/Supports/
├── GetActiveOrganization.php  → Session-based workspace management
├── RouteHelper.php            → Auto-loading route files
└── UserRoleContext.php        → Organization-aware role checking
```

## Implementasi

### GetActiveOrganization — Workspace Management

```php
class GetActiveOrganization
{
    public static function getSelected(): ?string   // Baca dari session
    public static function get(): ?Organization     // Baca + validate membership
    public static function set(string $id): bool    // Set + validate
    public static function setWithoutValidation()   // Set tanpa validate (registration flow)
    public static function clear(): void            // Hapus workspace aktif
    public static function hasSelected(): bool      // Cek apakah sudah terpilih
}
```

> Digunakan di: `HasOrganization` Trait, `HandleInertiaRequests` Middleware, `WorkspaceCreateAction`

### RouteHelper — Auto-Loading Route Files

```php
class RouteHelper
{
    /**
     * Auto-load all PHP files from a directory recursively.
     * Memungkinkan route splitting per domain.
     */
    public static function loadRoutesFromDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require $file->getPathname();
            }
        }
    }
}
```

**Penggunaan di `routes/web.php`:**

```php
// Satu baris ini me-load semua file di routes/web/*
RouteHelper::loadRoutesFromDirectory(__DIR__.'/web');
```

Sehingga route bisa di-split per domain:

```
routes/web/
├── auth.php        → Login, register, password reset
├── dashboard.php   → Dashboard page
├── projects.php    → Project CRUD
├── settings.php    → App settings
├── tasks.php       → Task CRUD + kanban + comments
├── team.php        → User management
└── workspace.php   → Workspace switching
```

### UserRoleContext — Role Checking

```php
class UserRoleContext
{
    // Check global role (Super Admin)
    public static function checkGlobalRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    // Check contextual role (bound to organization)
    public static function checkContextualRole(User $user, array $roles): bool
    {
        $orgId = GetActiveOrganization::getSelected();
        if ($orgId) {
            $orgRole = $user->getRoleInOrganization($orgId);
            if ($orgRole === 'admin') return true;
        }
        return $user->hasAnyRole($roles);
    }

    // Convenience methods
    public static function canManageProjects(): bool { ... }
    public static function canManageMembers(): bool { ... }
}
```

## Pola-Pola Kunci

### 1. Static Methods
Support class menggunakan static methods karena **stateless** — tidak perlu instance:

```php
GetActiveOrganization::getSelected();  // Tidak perlu new
UserRoleContext::canManageProjects();
```

### 2. Validation Layer
`set()` method selalu validate sebelum set:

```php
public static function set(string $organizationId): bool
{
    $user = Auth::user();
    if (! $user || ! $user->belongsToOrganization($organizationId)) {
        return false;  // Silent fail — return boolean
    }
    Session::put('organization_id', $organizationId);
    return true;
}
```

### 3. Unsafe Escape Hatch
Untuk flow tertentu yang belum punya user context (registration), sediakan method tanpa validasi:

```php
// Registration flow — user baru saja membuat org, belum ada di database
GetActiveOrganization::setWithoutValidation($organization->id);
```

---

**Referensi file:** `app/Supports/*.php`
