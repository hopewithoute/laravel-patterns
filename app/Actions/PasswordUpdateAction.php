<?php

namespace App\Actions;

use App\Data\PasswordUpdateData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Action to update user password.
 */
readonly class PasswordUpdateAction
{
    public function execute(PasswordUpdateData $data, User $user): void
    {
        DB::transaction(function () use ($data, $user) {
            $user->update([
                'password' => Hash::make($data->password),
            ]);
        });
    }
}
