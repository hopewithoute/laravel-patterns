<?php

namespace App\AI\Tools;

use App\AI\Runtime\Contracts\WorkspaceScopedTool;
use App\AI\Runtime\Tools\Attributes\RuntimeTool;
use App\AI\Runtime\Tools\Concerns\ResolvesRuntimeToolMetadata;
use App\AI\Runtime\Tools\Registry\LookupProjectsToolExecutor;
use App\Models\Organization;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Tools\Request;
use Stringable;

#[RuntimeTool(
    name: 'LookupProjectsTool',
    uiIdentifier: 'lookup_projects',
    label: 'Lookup projects',
    description: 'Search projects in the active organization workspace.',
    whenToUse: 'Use when the user mentions a project by name and the correct project identifier or canonical project name needs to be confirmed.',
    whenNotToUse: 'Do not use when the target project is already clear enough to create the task directly.',
    outputContract: 'Returns count and projects[] with project_id, project_name, is_active, and description.',
    capability: 'workspace.read',
    operation: 'read',
    maxAttempts: 1,
    scope: 'workspace',
)]
readonly class LookupProjectsTool implements WorkspaceScopedTool
{
    use ResolvesRuntimeToolMetadata;

    public function __construct(
        private LookupProjectsToolExecutor $executor,
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
            throw new \RuntimeException('Workspace context is required before invoking LookupProjectsTool.');
        }

        return json_encode(
            $this->executor->execute($request->all(), $this->organizationId),
            JSON_THROW_ON_ERROR,
        );
    }
}
