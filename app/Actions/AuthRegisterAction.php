<?php

namespace App\Actions;

use App\Data\RegisterData;
use App\Models\Organization;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Action to handle user registration.
 */
readonly class AuthRegisterAction
{
    /**
     * @return array{user: User, joined_organization: bool}
     */
    public function execute(RegisterData $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'is_active' => true,
            ]);

            event(new Registered($user));

            Auth::login($user);

            $joinedOrg = false;
            // If invite code provided, attach to organization
            if (! empty($data->invite_code)) {
                $organization = Organization::where('invite_code', $data->invite_code)->first();
                if ($organization) {
                    $organization->addMember($user, 'member');
                    GetActiveOrganization::setWithoutValidation($organization->id);
                    $joinedOrg = true;
                }
            }

            return [
                'user' => $user,
                'joined_organization' => $joinedOrg,
            ];
        });
    }
}
