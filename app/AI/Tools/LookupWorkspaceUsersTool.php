<?php

namespace App\AI\Tools;

use App\AI\Runtime\Contracts\WorkspaceScopedTool;
use App\AI\Runtime\Tools\Attributes\RuntimeTool;
use App\AI\Runtime\Tools\Concerns\ResolvesRuntimeToolMetadata;
use App\AI\Runtime\Tools\Registry\LookupWorkspaceUsersToolExecutor;
use App\Models\Organization;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Tools\Request;
use Stringable;

#[RuntimeTool(
    name: 'LookupWorkspaceUsersTool',
    uiIdentifier: 'lookup_workspace_users',
    label: 'Lookup workspace users',
    description: 'Search workspace members in the active organization workspace.',
    whenToUse: 'Use when the user wants to assign a task but the exact workspace member reference is unclear or ambiguous.',
    whenNotToUse: 'Do not use when the assignee is already clear, or when assign_to_me can be used instead.',
    outputContract: 'Returns count and users[] with user_id, user_name, email, and role.',
    capability: 'workspace.read',
    operation: 'read',
    maxAttempts: 1,
    scope: 'workspace',
)]
readonly class LookupWorkspaceUsersTool implements WorkspaceScopedTool
{
    use ResolvesRuntimeToolMetadata;

    public function __construct(
        private LookupWorkspaceUsersToolExecutor $executor,
        private ?string $organizationId = null,
    ) {}

    public function forWorkspace(Organization|string $organization): self
    {
        return new self(
            $this->executor,
            $organization instanceof Organization ? $organization->id : $organization,
        );
    }

    public function name(): string
    {
        return self::runtimeToolMetadata()->name;
    }

    public function description(): Stringable|string
    {
        return self::runtimeToolMetadata()->llmDescription();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string(),
            'limit' => $schema->integer()->min(1)->max(10),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if ($this->organizationId === null) {
            throw new \RuntimeException('Workspace context is required before invoking LookupWorkspaceUsersTool.');
        }

        return json_encode(
            $this->executor->execute($request->all(), $this->organizationId),
            JSON_THROW_ON_ERROR,
        );
    }
}
