<?php

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action to remove a user from an organization.
 */
readonly class UserRemoveAction
{
    public function execute(User $user, Organization $organization, User $currentUser): void
    {
        DB::transaction(function () use ($user, $organization, $currentUser) {
            // Can't remove yourself
            if ($user->id === $currentUser->id) {
                throw ValidationException::withMessages([
                    'error' => 'You cannot remove yourself from the team.',
                ]);
            }

            // Check user belongs to this org
            if (! $organization->hasMember($user)) {
                throw ValidationException::withMessages([
                    'error' => 'User does not belong to this organization.',
                ]);
            }

            $organization->removeMember($user);
        });
    }
}
