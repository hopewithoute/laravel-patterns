<?php

namespace App\AI\Actions;

use App\AI\Exceptions\AiToolException;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResolveWorkspaceAssigneeReferenceAction
{
    public function __construct(
        private Organization $organizationModel,
    ) {}

    public function execute(?string $reference, bool $assignToMe, string $organizationId, User $actor): ?User
    {
        if ($assignToMe) {
            return $actor;
        }

        $normalizedReference = $this->normalize($reference);

        if ($normalizedReference === null) {
            return null;
        }

        if (in_array(Str::lower($normalizedReference), ['me', 'myself', 'saya', 'aku'], true)) {
            return $actor;
        }

        $organization = $this->organizationModel->newQuery()->findOrFail($organizationId);
        $members = $organization->members();

        $exactId = (clone $members)
            ->where('users.id', $normalizedReference)
            ->first();

        if ($exactId instanceof User) {
            return $exactId;
        }

        $exactMatches = (clone $members)
            ->where(function (Builder $query) use ($normalizedReference): void {
                $query
                    ->whereRaw('LOWER(users.name) = ?', [Str::lower($normalizedReference)])
                    ->orWhereRaw('LOWER(users.email) = ?', [Str::lower($normalizedReference)]);
            })
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email']);

        if ($exactMatches->count() === 1) {
            return $exactMatches->first();
        }

        $partialMatches = (clone $members)
            ->where(function (Builder $query) use ($normalizedReference): void {
                $query
                    ->where('users.name', 'like', '%'.$normalizedReference.'%')
                    ->orWhere('users.email', 'like', '%'.$normalizedReference.'%');
            })
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email']);

        if ($partialMatches->count() === 1) {
            return $partialMatches->first();
        }

        if ($exactMatches->count() > 1 || $partialMatches->count() > 1) {
            throw new AiToolException($this->ambiguousMessage($exactMatches->count() > 1 ? $exactMatches : $partialMatches));
        }

        throw new AiToolException('No matching workspace user was found for assignment. Use lookup_workspace_users to find the correct assignee.');
    }

    private function normalize(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        $normalized = trim($reference);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param  Collection<int, User>  $matches
     */
    private function ambiguousMessage(Collection $matches): string
    {
        $examples = $matches->take(5)->map(
            fn (User $user): string => "{$user->name} ({$user->email})"
        )->implode(', ');

        return "Multiple workspace users matched that assignee reference: {$examples}. Use lookup_workspace_users to choose the right one.";
    }
}
