# Policy Authorization Pattern

> **Model-Based Access Control with Contextual Roles**

## Overview

This project uses **Laravel Policy** for authorization with an organization-aware approach. Each policy checks:

1. **Organization membership** — User must be a member of the same org
2. **Role-based permissions** — Uses `RoleAuth` enum for permission grouping
3. **Ownership check** — Some actions are only allowed by the assignee

## Directory Structure

```
app/Policies/
├── ProjectPolicy.php  → Authorization for Project CRUD
└── TaskPolicy.php     → Authorization for Task CRUD + operations
```

## Implementation

### TaskPolicy

```php
class TaskPolicy
{
    use HandlesAuthorization;

    // All users can view tasks
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Must be in the same organization
    public function view(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id);
    }

    // Update: Must have management role OR be the assignee
    public function update(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id) && (
            in_array(session('roles'), RoleAuth::canManageProjects())
            || $task->assigned_to === $user->id
        );
    }

    // Delete: Only management role
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
    // Create: Only Owner and Admin
    public function create(User $user): bool
    {
        return in_array(session('roles'), RoleAuth::canManageProjects());
    }

    // Delete: Only Owner
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

> 🔸* Members can only update tasks assigned to them

## Usage in Controller

### Inline Authorization

```php
public function complete(Task $task, TaskCompleteAction $action): RedirectResponse
{
    $this->authorize('update', $task); // Throws 403 if unauthorized

    $action->execute($task);

    return redirect()->back()->with('success', 'Task completed.');
}
```

### Middleware Authorization

```php
// In route file
Route::middleware(['auth', 'contextual_role:Owner,Admin'])
    ->group(function () {
        Route::resource('projects', ProjectController::class);
    });
```

## Key Patterns

### 1. Permission Groups in Enum, Not in Policy
Policies only **check** permissions; grouping logic is in the Enum:

```php
// Enum defines permission group
RoleAuth::canManageProjects() // ['Owner', 'Admin']

// Policy only checks
in_array(session('roles'), RoleAuth::canManageProjects())
```

### 2. Organization + Role Dual Check
Always check **organization membership** first, then **role**:

```php
return $user->belongsToOrganization($task->organization_id)  // ← Org check first
    && in_array(session('roles'), RoleAuth::canManageProjects()); // ← Then role check
```

### 3. Assignee Override
Members can update tasks assigned to them:

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

**Reference files:** `app/Policies/*.php`
