<?php

namespace App\AI\Runtime\Tools\Registry;

use App\AI\Actions\LookupWorkspaceUsersAction;
use App\AI\Data\LookupWorkspaceUsersToolData;

readonly class LookupWorkspaceUsersToolExecutor
{
    public function __construct(
        private LookupWorkspaceUsersAction $lookupWorkspaceUsersAction,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(array $input, string $organizationId): array
    {
        $data = LookupWorkspaceUsersToolData::validateAndCreate($input);
        $matches = $this->lookupWorkspaceUsersAction->execute(
            $organizationId,
            $data->query,
            $data->limit ?? 5,
        );

        return [
            'query' => $data->query,
            'count' => count($matches),
            'users' => $matches,
        ];
    }
}
