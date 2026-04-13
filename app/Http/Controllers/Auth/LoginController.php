<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AuthLoginAction;
use App\Data\LoginData;
use App\Http\Controllers\Controller;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'title' => 'Sign In',
        ]);
    }

    /**
     * Handle an authentication attempt.
     */
    public function store(LoginData $data, AuthLoginAction $action): RedirectResponse
    {
        $action->execute($data);

        return GetActiveOrganization::getSelected()
            ? redirect()->intended(route('dashboard.index'))
            : redirect()->route('workspace.select');
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        GetActiveOrganization::clear();

        return redirect()->route('login');
    }
}
