<?php

namespace App\Policies;

use App\Enums\RoleAuth;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for Task model authorization.
 */
class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view tasks
    }

    /**
     * Determine if user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id);
    }

    /**
     * Determine if user can create tasks.
     */
    public function create(User $user): bool
    {
        // All members can create tasks
        return true;
    }

    /**
     * Determine if user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        // Can update if:
        // 1. Same organization and has management role
        // 2. Is the assignee
        return $user->belongsToOrganization($task->organization_id) && (
            in_array(session('roles'), RoleAuth::canManageProjects())
            || $task->assigned_to === $user->id
        );
    }

    /**
     * Determine if user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        // Can delete if has management role
        return $user->belongsToOrganization($task->organization_id)
            && in_array(session('roles'), RoleAuth::canManageProjects());
    }

    /**
     * Determine if user can assign the task.
     */
    public function assign(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id)
            && in_array(session('roles'), RoleAuth::canManageProjects());
    }
}
