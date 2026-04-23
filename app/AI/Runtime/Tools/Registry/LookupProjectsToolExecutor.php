<?php

namespace App\AI\Runtime\Tools\Registry;

use App\AI\Actions\LookupWorkspaceProjectsAction;
use App\AI\Data\LookupProjectsToolData;

readonly class LookupProjectsToolExecutor
{
    public function __construct(
        private LookupWorkspaceProjectsAction $lookupWorkspaceProjectsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(array $input, string $organizationId): array
    {
        $data = LookupProjectsToolData::validateAndCreate($input);
        $matches = $this->lookupWorkspaceProjectsAction->execute(
            $organizationId,
            $data->query,
            $data->limit ?? 5,
        );

        return [
            'query' => $data->query,
            'count' => count($matches),
            'projects' => $matches,
        ];
    }
}
