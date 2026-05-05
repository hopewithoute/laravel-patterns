# Support Helper Pattern

> **Utility Classes for Cross-Cutting Concerns**

## Overview

`app/Supports/` contains **stateless utility classes** that provide helper functions for cross-cutting concerns — things needed in many places but that aren't domain business logic.

## Directory Structure

```
app/Supports/
├── GetActiveOrganization.php  → Session-based workspace management
├── RouteHelper.php            → Auto-loading route files
└── UserRoleContext.php        → Organization-aware role checking
```

## Implementation

### GetActiveOrganization — Workspace Management

```php
class GetActiveOrganization
{
    public static function getSelected(): ?string   // Read from session
    public static function get(): ?Organization     // Read + validate membership
    public static function set(string $id): bool    // Set + validate
    public static function setWithoutValidation()   // Set without validate (registration flow)
    public static function clear(): void            // Clear active workspace
    public static function hasSelected(): bool      // Check if one is selected
}
```

> Used in: `HasOrganization` Trait, `HandleInertiaRequests` Middleware, `WorkspaceCreateAction`

### RouteHelper — Auto-Loading Route Files

```php
class RouteHelper
{
    /**
     * Auto-load all PHP files from a directory recursively.
     * Enables route splitting per domain.
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

**Usage in `routes/web.php`:**

```php
// This single line loads all files in routes/web/*
RouteHelper::loadRoutesFromDirectory(__DIR__.'/web');
```

This allows routes to be split per domain:

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

## Key Patterns

### 1. Static Methods
Support classes use static methods because they are **stateless** — no instance needed:

```php
GetActiveOrganization::getSelected();  // No need for new
UserRoleContext::canManageProjects();
```

### 2. Validation Layer
The `set()` method always validates before setting:

```php
public static function set(string $organizationId): bool
{
    $user = Auth::user();
    if (! $user || ! $user->belongsToOrganization($organizationId)) {
        return false;  // Silent fail — returns boolean
    }
    Session::put('organization_id', $organizationId);
    return true;
}
```

### 3. Unsafe Escape Hatch
For flows that don't have user context yet (registration), provide a method without validation:

```php
// Registration flow — user just created org, not in database yet
GetActiveOrganization::setWithoutValidation($organization->id);
```

---

**Reference files:** `app/Supports/*.php`
