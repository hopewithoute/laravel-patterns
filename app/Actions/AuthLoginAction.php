<?php

namespace App\Actions;

use App\Data\LoginData;
use App\Supports\GetActiveOrganization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Action to handle user login.
 */
readonly class AuthLoginAction
{
    public function execute(LoginData $data): void
    {
        if (! Auth::attempt(['email' => $data->email, 'password' => $data->password], $data->remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        session()->regenerate();

        $user = Auth::user();

        // Get user's first organization and set as active
        $firstOrg = $user->organizations()->first();

        if ($firstOrg) {
            GetActiveOrganization::setWithoutValidation($firstOrg->id);
        }
    }
}
