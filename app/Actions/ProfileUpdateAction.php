<?php

namespace App\Actions;

use App\Data\ProfileUpdateData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action to update user profile.
 */
readonly class ProfileUpdateAction
{
    public function execute(ProfileUpdateData $data, User $user): User
    {
        return DB::transaction(function () use ($data, $user) {
            $user->update([
                'name' => $data->name,
                'email' => $data->email,
            ]);

            return $user->fresh();
        });
    }
}
