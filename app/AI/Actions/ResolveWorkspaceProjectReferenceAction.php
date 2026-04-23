<?php

namespace App\AI\Actions;

use App\AI\Exceptions\AiToolException;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResolveWorkspaceProjectReferenceAction
{
    public function __construct(
        private Project $projectModel,
    ) {}

    public function execute(?string $reference, string $organizationId): Project
    {
        $normalizedReference = $this->normalize($reference);

        if ($normalizedReference === null) {
            $availableProjects = $this->baseQuery($organizationId)
                ->active()
                ->orderBy('name')
                ->get();

            if ($availableProjects->count() === 1) {
                return $availableProjects->first();
            }
        }

        if ($normalizedReference === null) {
            throw new AiToolException('Project is required because the active workspace has multiple projects. Use lookup_projects if needed.');
        }

        $exactId = $this->baseQuery($organizationId)
            ->whereKey($normalizedReference)
            ->first();

        if ($exactId instanceof Project) {
            return $exactId;
        }

        $exactNameMatches = $this->baseQuery($organizationId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($normalizedReference)])
            ->orderBy('name')
            ->get();

        if ($exactNameMatches->count() === 1) {
            return $exactNameMatches->first();
        }

        $partialMatches = $this->baseQuery($organizationId)
            ->where('name', 'like', '%'.$normalizedReference.'%')
            ->orderBy('name')
            ->get();

        if ($partialMatches->count() === 1) {
            return $partialMatches->first();
        }

        if ($exactNameMatches->count() > 1 || $partialMatches->count() > 1) {
            throw new AiToolException($this->ambiguousMessage('project', $exactNameMatches->count() > 1 ? $exactNameMatches : $partialMatches));
        }

        throw new AiToolException('No matching project was found in the active workspace. Use lookup_projects to find the correct project.');
    }

    private function baseQuery(string $organizationId)
    {
        return $this->projectModel
            ->newQuery()
            ->withoutOrganizationScope()
            ->where('organization_id', $organizationId);
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
     * @param  Collection<int, Project>  $matches
     */
    private function ambiguousMessage(string $type, Collection $matches): string
    {
        $examples = $matches->take(5)->map(
            fn (Project $project): string => "{$project->name} ({$project->id})"
        )->implode(', ');

        return "Multiple {$type} matches were found in the active workspace: {$examples}. Use lookup_projects to choose the right one.";
    }
}
