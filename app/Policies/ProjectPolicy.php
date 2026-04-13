<?php

namespace App\Policies;

use App\Enums\RoleAuth;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for Project model authorization.
 */
class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view projects
    }

    /**
     * Determine if user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        // Must belong to same organization
        return $user->belongsToOrganization($project->organization_id);
    }

    /**
     * Determine if user can create projects.
     */
    public function create(User $user): bool
    {
        // Only Owner and Admin can create
        return in_array(
            session('roles'),
            RoleAuth::canManageProjects()
        );
    }

    /**
     * Determine if user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        // Must be in same organization and have management role
        return $user->belongsToOrganization($project->organization_id)
            && in_array(session('roles'), RoleAuth::canManageProjects());
    }

    /**
     * Determine if user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        // Only Owner can delete
        return $user->belongsToOrganization($project->organization_id)
            && RoleAuth::isOwner();
    }

    /**
     * Determine if user can restore the project.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine if user can force delete the project.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }
}
