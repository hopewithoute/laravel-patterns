<?php

namespace App\Http\Controllers;

use App\Actions\WorkspaceCreateAction;
use App\Actions\WorkspaceSetAction;
use App\Data\WorkspaceCreateData;
use App\Data\WorkspaceSetData;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    /**
     * Show workspace selection page.
     */
    public function select(Request $request): Response
    {
        $user = $request->user();

        $organizations = $user->organizations()
            ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.logo']);

        return Inertia::render('Workspace/Select', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Set the active workspace.
     */
    public function set(WorkspaceSetData $data, WorkspaceSetAction $action, Request $request): RedirectResponse
    {
        $action->execute($data, $request->user());

        return redirect()->intended(route('dashboard.index'))
            ->with('success', 'Workspace selected.');
    }

    /**
     * Show create organization form.
     */
    public function create(): Response
    {
        return Inertia::render('Workspace/Create');
    }

    /**
     * Store new organization.
     */
    public function store(WorkspaceCreateData $data, WorkspaceCreateAction $action, Request $request): RedirectResponse
    {
        $result = $action->execute($data, $request->user());

        $message = 'Workspace created successfully.';
        if ($result['invited_count'] > 0) {
            $message .= " {$result['invited_count']} team member(s) invited.";
        }

        return redirect()->route('dashboard.index')
            ->with('success', $message);
    }
}
