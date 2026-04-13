<?php

namespace App\Actions;

use App\Data\UserInviteData;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action to invite a user to an organization.
 */
readonly class UserInviteAction
{
    public function execute(UserInviteData $data, Organization $organization): User
    {
        return DB::transaction(function () use ($data, $organization) {
            $user = User::where('email', $data->email)->firstOrFail();

            // Check if user already in organization
            if ($organization->hasMember($user)) {
                throw ValidationException::withMessages([
                    'email' => 'User is already a member of this organization.',
                ]);
            }

            // Add user to organization
            $organization->addMember($user, 'member');

            return $user;
        });
    }
}
