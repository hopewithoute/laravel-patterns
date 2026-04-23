<?php

namespace App\AI\Actions;

use App\Models\Project;

class LookupWorkspaceProjectsAction
{
    public function __construct(
        private Project $projectModel,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $organizationId, ?string $query = null, int $limit = 5): array
    {
        return $this->projectModel
            ->newQuery()
            ->withoutOrganizationScope()
            ->where('organization_id', $organizationId)
            ->when($query !== null && trim($query) !== '', fn ($builder) => $builder->where('name', 'like', '%'.trim($query).'%'))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'is_active', 'description'])
            ->map(fn (Project $project): array => [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'is_active' => (bool) $project->is_active,
                'description' => $project->description,
            ])
            ->all();
    }
}
