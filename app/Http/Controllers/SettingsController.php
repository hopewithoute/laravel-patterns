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
        return Inertia::render('Settings/Index', [
            'organization' => fn () => $this->getOrganization(),
            'tokens' => fn () => $this->getTokens($request),
            'newToken' => null,
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

    /**
     * Create a new API token.
     */
    public function storeToken(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $token = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'] ?? ['*'],
            isset($validated['expires_at']) ? now()->parse($validated['expires_at']) : null
        );

        return Inertia::render('Settings/Index', [
            'organization' => fn () => $this->getOrganization(),
            'tokens' => fn () => $this->getTokens($request),
            'newToken' => $token->plainTextToken,
        ]);
    }

    /**
     * Revoke an API token.
     */
    public function destroyToken(Request $request, int $id): RedirectResponse
    {
        $token = $request->user()->tokens()->where('id', $id)->firstOrFail();
        $token->delete();

        return back()->with('success', 'Token revoked successfully.');
    }

    /**
     * Get current organization.
     */
    private function getOrganization(): ?Organization
    {
        $organizationId = GetActiveOrganization::getSelected();

        return $organizationId ? Organization::find($organizationId) : null;
    }

    /**
     * Get user's tokens formatted for API.
     */
    private function getTokens(Request $request): array
    {
        return $request->user()->tokens()->latest()->get()->map(fn ($token) => [
            'id' => $token->id,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'last_used_at' => $token->last_used_at?->diffForHumans(),
            'expires_at' => $token->expires_at?->toDateString(),
            'created_at' => $token->created_at->diffForHumans(),
        ])->toArray();
    }
}
