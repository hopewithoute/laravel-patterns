<?php

namespace App\Http\Controllers;

use App\Actions\PasswordUpdateAction;
use App\Actions\ProfileUpdateAction;
use App\Data\PasswordUpdateData;
use App\Data\ProfileUpdateData;
use App\Models\Organization;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show settings page.
     */
    public function index(Request $request): Response
    {
        $organizationId = GetActiveOrganization::getSelected();
        $organization = $organizationId ? Organization::find($organizationId) : null;

        return Inertia::render('Settings/Index', [
            'organization' => $organization,
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(ProfileUpdateData $data, ProfileUpdateAction $action, Request $request): RedirectResponse
    {
        $action->execute($data, $request->user());

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(PasswordUpdateData $data, PasswordUpdateAction $action, Request $request): RedirectResponse
    {
        $action->execute($data, $request->user());

        return back()->with('success', 'Password updated successfully.');
    }
}
