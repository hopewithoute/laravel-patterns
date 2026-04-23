# Policy Authorization Pattern

> **Model-Based Access Control with Contextual Roles**

## Overview

Project ini menggunakan **Laravel Policy** untuk authorization dengan pendekatan organization-aware. Setiap policy memeriksa:

1. **Organization membership** — User harus anggota org yang sama
2. **Role-based permissions** — Menggunakan `RoleAuth` enum untuk permission grouping
3. **Ownership check** — Beberapa aksi hanya boleh oleh assignee

## Struktur Direktori

```
app/Policies/
├── ProjectPolicy.php  → Authorization untuk Project CRUD
└── TaskPolicy.php     → Authorization untuk Task CRUD + operations
```

## Implementasi

### TaskPolicy

```php
class TaskPolicy
{
    use HandlesAuthorization;

    // Semua user bisa melihat tasks
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Harus satu organisasi
    public function view(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id);
    }

    // Update: Harus management role ATAU assignee
    public function update(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id) && (
            in_array(session('roles'), RoleAuth::canManageProjects())
            || $task->assigned_to === $user->id
        );
    }

    // Delete: Hanya management role
    public function delete(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id)
            && in_array(session('roles'), RoleAuth::canManageProjects());
    }
}
```

### ProjectPolicy

```php
class ProjectPolicy
{
    // Create: Hanya Owner dan Admin
    public function create(User $user): bool
    {
        return in_array(session('roles'), RoleAuth::canManageProjects());
    }

    // Delete: Hanya Owner
    public function delete(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization_id)
            && RoleAuth::isOwner();
    }
}
```

## Authorization Matrix

| Action         | Member | Admin | Owner | Super Admin |
|----------------|--------|-------|-------|-------------|
| View Tasks     | ✅     | ✅    | ✅    | ✅          |
| Create Tasks   | ✅     | ✅    | ✅    | ✅          |
| Update Tasks   | 🔸*   | ✅    | ✅    | ✅          |
| Delete Tasks   | ❌     | ✅    | ✅    | ✅          |
| Create Project | ❌     | ✅    | ✅    | ✅          |
| Delete Project | ❌     | ❌    | ✅    | ✅          |

> 🔸* Member hanya bisa update task yang di-assign ke dirinya

## Penggunaan di Controller

### Inline Authorization

```php
public function complete(Task $task, TaskCompleteAction $action): RedirectResponse
{
    $this->authorize('update', $task); // Throws 403 jika unauthorized

    $action->execute($task);

    return redirect()->back()->with('success', 'Task completed.');
}
```

### Middleware Authorization

```php
// Di route file
Route::middleware(['auth', 'contextual_role:Owner,Admin'])
    ->group(function () {
        Route::resource('projects', ProjectController::class);
    });
```

## Pola-Pola Kunci

### 1. Permission Group di Enum, Bukan di Policy
Policy hanya **check** permission, logika grouping ada di Enum:

```php
// Enum mendefinisikan permission group
RoleAuth::canManageProjects() // ['Owner', 'Admin']

// Policy hanya check
in_array(session('roles'), RoleAuth::canManageProjects())
```

### 2. Organization + Role Dual Check
Selalu check **organization membership** terlebih dahulu, baru **role**:

```php
return $user->belongsToOrganization($task->organization_id)  // ← Org check dulu
    && in_array(session('roles'), RoleAuth::canManageProjects()); // ← Baru role check
```

### 3. Assignee Override
Member boleh update task yang di-assign ke dirinya:

```php
public function update(User $user, Task $task): bool
{
    return $user->belongsToOrganization($task->organization_id) && (
        in_array(session('roles'), RoleAuth::canManageProjects())
        || $task->assigned_to === $user->id  // ← Assignee override
    );
}
```

---

**Referensi file:** `app/Policies/*.php`
