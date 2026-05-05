# Multi-Tenancy Pattern

> **Organization-Scoped Data Isolation via Global Scope + Trait**

## Overview

Project ini menerapkan **multi-tenancy** (multi-workspace) di level application. Setiap data (Project, Task, Comment) ter-isolasi per Organization. Implementasi dilakukan melalui:

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
│     → Cek session('organization_id') ada             │
│     → Redirect ke workspace.select jika belum        │
├──────────────────────────────────────────────────────┤
│  2. ContextualRoleMiddleware                         │
│     → Cek role user di organizasi aktif              │
│     → Super Admin bypass semua                       │
├──────────────────────────────────────────────────────┤
│  3. Controller / Query                               │
│     → Model dengan HasOrganization trait             │
│     → Global Scope otomatis filter by org_id         │
│     → Insert otomatis set org_id                     │
└──────────────────────────────────────────────────────┘
```

## Implementasi

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

**Usage di Model:**

```php
class Task extends Model
{
    use HasFactory, HasOrganization, HasUuids;
    // Semua query otomatis di-filter by organization_id!
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
    // Baca org_id dari session
    public static function getSelected(): ?string
    {
        return Session::get('organization_id');
    }

    // Set dengan validasi membership
    public static function set(string $organizationId): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->belongsToOrganization($organizationId)) {
            return false;
        }
        Session::put('organization_id', $organizationId);
        return true;
    }

    // Set tanpa validasi (untuk registration/creation flow)
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
        // Super Admin tanpa workspace → allowed (global dashboard)
        if (RoleAuth::isSuperAdmin() && ! Session::has('organization_id')) {
            return $next($request);
        }

        // User lain → harus punya organization
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

## Pola-Pola Kunci

### 1. Transparent Scoping
Developer **tidak perlu** menambahkan `where('organization_id', ...)` di setiap query:

```php
// ✅ Otomatis ter-filter
Task::all();
Task::where('status', 'Todo')->get();
Project::active()->get();

// ❌ Tidak perlu manual (kecuali Super Admin)
Task::where('organization_id', session('org_id'))->get();
```

### 2. Bypass Scope untuk Admin
Gunakan `withoutOrganizationScope()` hanya untuk Super Admin dashboard:

```php
Task::withoutOrganizationScope()->count(); // Global count
```

### 3. Session-based Workspace
Workspace aktif disimpan di session, bukan URL:

```php
// Switch workspace
GetActiveOrganization::set($newOrgId);

// Cek workspace
GetActiveOrganization::hasSelected();
```

### 4. Invite Code System
Organization punya invite code untuk onboarding:

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

API menggunakan header `X-Organization` instead of session untuk multi-tenancy.

### ApiSetOrganization Middleware

```php
class ApiSetOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->header('X-Organization');

        if ($organizationId) {
            // Set di session agar GetActiveOrganization bisa bekerja
            Session::put('organization_id', $organizationId);
        }

        return $next($request);
    }
}
```

**Registrasi di bootstrap/app.php:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \App\Http\Middleware\ApiSetOrganization::class,
    ]);
})
```

### Penggunaan di API Request

```bash
curl -H "Authorization: Bearer {token}" \
     -H "X-Organization: {organization_id}" \
     https://api.example.com/api/tasks
```

### Cara Kerja

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
HasOrganization Scope filter data
```

### Testing API Multi-tenancy

```php
$this->withHeader('X-Organization', $org->id)
     ->getJson('/api/tasks')
     ->assertOk();
```

---

**Referensi file:**
- `app/Traits/HasOrganization.php`
- `app/Supports/GetActiveOrganization.php`
- `app/Http/Middleware/EnsureWorkspaceSelected.php`
- `app/Http/Middleware/ApiSetOrganization.php`
- `app/Http/Middleware/ContextualRoleMiddleware.php`
- `app/Models/Organization.php`
