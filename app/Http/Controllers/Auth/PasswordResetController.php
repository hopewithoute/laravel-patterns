<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AuthForgotPasswordAction;
use App\Actions\AuthResetPasswordAction;
use App\Data\ForgotPasswordData;
use App\Data\ResetPasswordData;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    /**
     * Show forgot password form.
     */
    public function request(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Send password reset link.
     */
    public function email(ForgotPasswordData $data, AuthForgotPasswordAction $action): RedirectResponse
    {
        $status = $action->execute($data);

        return back()->with('success', __($status));
    }

    /**
     * Show reset password form.
     */
    public function reset(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle password reset.
     */
    public function update(ResetPasswordData $data, AuthResetPasswordAction $action): RedirectResponse
    {
        $status = $action->execute($data);

        return redirect()->route('login')->with('success', __($status));
    }
}
