<?php

namespace App\Actions;

use App\Data\ForgotPasswordData;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Action to handle forgot password requests.
 */
readonly class AuthForgotPasswordAction
{
    public function execute(ForgotPasswordData $data): string
    {
        $status = Password::sendResetLink($data->all());

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $status;
    }
}
