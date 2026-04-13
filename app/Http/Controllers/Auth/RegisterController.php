<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AuthRegisterAction;
use App\Data\RegisterData;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Show registration form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'title' => 'Create Account',
            'invite_code' => $request->query('invite'),
            'organization' => $request->query('invite')
                ? Organization::where('invite_code', $request->query('invite'))->first(['name', 'invite_code'])
                : null,
        ]);
    }

    /**
     * Handle registration.
     */
    public function store(RegisterData $data, AuthRegisterAction $action): RedirectResponse
    {
        $result = $action->execute($data);

        if ($result['joined_organization']) {
            return redirect()->route('dashboard.index')
                ->with('success', 'Account created and joined organization successfully.');
        }

        return redirect()->route('workspace.select')
            ->with('success', 'Account created successfully. Please select or create a workspace.');
    }
}
