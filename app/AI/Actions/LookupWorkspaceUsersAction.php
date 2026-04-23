<?php

namespace App\AI\Actions;

use App\Models\Organization;
use App\Models\User;

class LookupWorkspaceUsersAction
{
    public function __construct(
        private Organization $organizationModel,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $organizationId, ?string $query = null, int $limit = 5): array
    {
        $organization = $this->organizationModel->newQuery()->findOrFail($organizationId);

        return $organization->members()
            ->when($query !== null && trim($query) !== '', function ($builder) use ($query): void {
                $builder->where(function ($queryBuilder) use ($query): void {
                    $queryBuilder
                        ->where('users.name', 'like', '%'.trim($query).'%')
                        ->orWhere('users.email', 'like', '%'.trim($query).'%');
                });
            })
            ->orderBy('users.name')
            ->limit($limit)
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $user): array => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot?->role,
            ])
            ->all();
    }
}
