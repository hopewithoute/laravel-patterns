# Multi-Tenancy Pattern

> **Organization-Scoped Data Isolation via Global Scope + Trait**

## Overview

This project implements **multi-tenancy** (multi-workspace) at the application level. All data (Project, Task, Comment) is isolated per Organization. Implementation is done through:

1. **`HasOrganization` Trait** — Global Scope + auto-fill `organization_id`
2. **`GetActiveOrganization` Support** — Session-based workspace switching
3. **`EnsureWorkspaceSelected` Middleware** — Route protection
4. **`ContextualRoleMiddleware`** — Organization-aware authorization

## Architecture

```
┌──────────────────────────────────────────────────────┐
│                    HTTP Request                       │
├──────────────────────────────────────────────────────┤
│  1. EnsureWorkspaceSelected Middleware               │
│     → Checks session('organization_id') exists       │
│     → Redirects to workspace.select if missing       │
├──────────────────────────────────────────────────────┤
│  2. ContextualRoleMiddleware                         │
│     → Checks user role in active organization        │
│     → Super Admin bypasses all checks                │
├──────────────────────────────────────────────────────┤
│  3. Controller / Query                               │
│     → Model with HasOrganization trait               │
│     → Global Scope automatically filters by org_id   │
│     → Insert automatically sets org_id               │
└──────────────────────────────────────────────────────┘
```

## Implementation

### 1. `HasOrganization` Trait

```php
<?php

namespace App\Traits;

trait HasOrganization
{
    protected static function bootHasOrganization(): void
    {
        // Global Scope — filter ALL SELECT queries automatically
        static::addGlobalScope('organization', new class implements Scope {
            public function apply(Builder $builder, Model $model)
            {
                $orgId = GetActiveOrganization::getSelected();
                if ($orgId) {
                    $builder->where($model->getTable().'.organization_id', $orgId);
                }
            }
        });

        // Auto-fill — set organization_id on INSERT
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $orgId = GetActiveOrganization::getSelected();
                if ($orgId) {
                    $model->organization_id = $orgId;
                }
            }
        });
    }

    // Relationship
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // Bypass scope (Super Admin only)
    public function scopeWithoutOrganizationScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }
}
```

**Usage in Model:**

```php
class Task extends Model
{
    use HasFactory, HasOrganization, HasUuids;
    // All queries are automatically filtered by organization_id!
}

class Project extends Model
{
    use HasFactory, HasOrganization, HasUuids;
}

class Comment extends Model
{
    use HasFactory, HasOrganization, HasUuids;
}
```

### 2. `GetActiveOrganization` Support

```php
class GetActiveOrganization
{
    // Read org_id from session
    public static function getSelected(): ?string
    {
        return Session::get('organization_id');
    }

    // Set with membership validation
    public static function set(string $organizationId): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->belongsToOrganization($organizationId)) {
            return false;
        }
        Session::put('organization_id', $organizationId);
        return true;
    }

    // Set without validation (for registration/creation flow)
    public static function setWithoutValidation(string $organizationId): void
    {
        Session::put('organization_id', $organizationId);
    }

    // Get full organization model (validated)
    public static function get(): ?Organization
    {
        $id = self::getSelected();
        $user = Auth::user();
        // Validate user is member — cached with once()
        return once(fn () => $user->organizations()->find($id));
    }
}
```

### 3. `EnsureWorkspaceSelected` Middleware

```php
class EnsureWorkspaceSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // Super Admin without workspace → allowed (global dashboard)
        if (RoleAuth::isSuperAdmin() && ! Session::has('organization_id')) {
            return $next($request);
        }

        // Other users → must have organization
        if (! Session::has('organization_id')) {
            return Redirect::route('workspace.select');
        }

        return $next($request);
    }
}
```

### 4. Many-to-Many Organization ↔ User

```php
// User Model
public function organizations(): BelongsToMany
{
    return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id')
        ->withPivot(['role', 'joined_at']);
}

public function belongsToOrganization(string $organizationId): bool
{
    return $this->organizations()->where('organizations.id', $organizationId)->exists();
}

public function getRoleInOrganization(string $organizationId): ?string
{
    $pivot = $this->organizations()->where('organizations.id', $organizationId)->first();
    return $pivot?->pivot->role;
}
```

## Data Isolation Flow

```
User A (Org: Acme)          User B (Org: Globex)
    │                            │
    ▼                            ▼
Task::all()                 Task::all()
    │                            │
    ▼                            ▼
HasOrganization Scope       HasOrganization Scope
WHERE org_id = 'acme'       WHERE org_id = 'globex'
    │                            │
    ▼                            ▼
[Task 1, Task 2]            [Task 5, Task 6]

→ Data completely isolated!
```

## Key Patterns

### 1. Transparent Scoping
Developers **do not need** to add `where('organization_id', ...)` to every query:

```php
// ✅ Automatically filtered
Task::all();
Task::where('status', 'Todo')->get();
Project::active()->get();

// ❌ No manual filtering needed (except for Super Admin)
Task::where('organization_id', session('org_id'))->get();
```

### 2. Bypass Scope for Admin
Use `withoutOrganizationScope()` only for Super Admin dashboard:

```php
Task::withoutOrganizationScope()->count(); // Global count
```

### 3. Session-based Workspace
The active workspace is stored in session, not URL:

```php
// Switch workspace
GetActiveOrganization::set($newOrgId);

// Check workspace
GetActiveOrganization::hasSelected();
```

### 4. Invite Code System
Organizations have invite codes for onboarding:

```php
protected static function booted(): void
{
    static::creating(function (self $organization) {
        if (empty($organization->invite_code)) {
            $organization->invite_code = strtoupper(Str::random(8));
        }
    });
}
```

## API Multi-tenancy

API uses `X-Organization` header instead of session for multi-tenancy.

### ApiSetOrganization Middleware

```php
class ApiSetOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->header('X-Organization');

        if ($organizationId) {
            // Set in session so GetActiveOrganization works
            Session::put('organization_id', $organizationId);
        }

        return $next($request);
    }
}
```

**Registration in bootstrap/app.php:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \App\Http\Middleware\ApiSetOrganization::class,
    ]);
})
```

### Usage in API Requests

```bash
curl -H "Authorization: Bearer {token}" \
     -H "X-Organization: {organization_id}" \
     https://api.example.com/api/tasks
```

### How It Works

```
API Request
    │
    ▼
X-Organization Header
    │
    ▼
ApiSetOrganization Middleware
    │
    ▼
Session::put('organization_id')
    │
    ▼
GetActiveOrganization::getSelected()
    │
    ▼
HasOrganization Scope filters data
```

### Testing API Multi-tenancy

```php
$this->withHeader('X-Organization', $org->id)
     ->getJson('/api/tasks')
     ->assertOk();
```

---

**Reference files:**
- `app/Traits/HasOrganization.php`
- `app/Supports/GetActiveOrganization.php`
- `app/Http/Middleware/EnsureWorkspaceSelected.php`
- `app/Http/Middleware/ApiSetOrganization.php`
- `app/Http/Middleware/ContextualRoleMiddleware.php`
- `app/Models/Organization.php`
