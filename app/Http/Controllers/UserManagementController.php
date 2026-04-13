<?php

namespace App\Http\Controllers;

use App\Actions\OrganizationRegenerateInviteCodeAction;
use App\Actions\UserInviteAction;
use App\Actions\UserRemoveAction;
use App\Data\UserInviteData;
use App\Models\Organization;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * List team members.
     */
    public function index(): Response|RedirectResponse
    {
        $organizationId = GetActiveOrganization::getSelected();

        if (! $organizationId) {
            return redirect()->route('workspace.select');
        }

        $organization = Organization::with('members')->findOrFail($organizationId);

        return Inertia::render('Team/Index', [
            'organization' => $organization,
            'members' => $organization->members()->paginate(20),
        ]);
    }

    /**
     * Invite user to organization.
     */
    public function invite(UserInviteData $data, UserInviteAction $action): RedirectResponse
    {
        $organizationId = GetActiveOrganization::getSelected();
        $organization = Organization::findOrFail($organizationId);

        $user = $action->execute($data, $organization);

        return back()->with('success', "Invited {$user->email} to your team.");
    }

    /**
     * Remove user from organization.
     */
    public function remove(User $user, UserRemoveAction $action, Request $request): RedirectResponse
    {
        $organizationId = GetActiveOrganization::getSelected();
        $organization = Organization::findOrFail($organizationId);

        $action->execute($user, $organization, $request->user());

        return back()->with('success', "Removed {$user->name} from the team.");
    }

    /**
     * Get organization invite code.
     */
    public function inviteCode(): Response
    {
        $organizationId = GetActiveOrganization::getSelected();
        $organization = Organization::findOrFail($organizationId);

        return Inertia::render('Team/Invite', [
            'organization' => $organization,
        ]);
    }

    /**
     * Regenerate invite code.
     */
    public function regenerateCode(OrganizationRegenerateInviteCodeAction $action): RedirectResponse
    {
        $organizationId = GetActiveOrganization::getSelected();
        $organization = Organization::findOrFail($organizationId);

        $action->execute($organization);

        return back()->with('success', 'Invite code regenerated.');
    }
}
