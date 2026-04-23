<?php

namespace App\AI\Tools;

use App\AI\Runtime\Contracts\WorkspaceScopedTool;
use App\AI\Runtime\Tools\Attributes\RuntimeTool;
use App\AI\Runtime\Tools\Concerns\ResolvesRuntimeToolMetadata;
use App\AI\Runtime\Tools\Registry\CreateTaskToolExecutor;
use App\Enums\Priority;
use App\Models\Organization;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Tools\Request;
use Stringable;

#[RuntimeTool(
    name: 'CreateTaskTool',
    uiIdentifier: 'create_task',
    label: 'Create task',
    description: 'Create a new task in the active organization workspace.',
    whenToUse: 'Use when the user wants to create a task and enough context is available to resolve the project and assignee.',
    whenNotToUse: 'Do not use for listing tasks, listing projects, listing workspace users, or when the project or assignee is ambiguous and should be looked up first.',
    requiredInputs: ['title'],
    outputContract: 'Returns task_id, project_id, project_name, title, status, priority, due_date, assigned_to, and assigned_to_name.',
    capability: 'task.create',
    operation: 'write',
    maxAttempts: 1,
    scope: 'workspace',
)]
readonly class CreateTaskTool implements WorkspaceScopedTool
{
    use ResolvesRuntimeToolMetadata;

    public function __construct(
        private CreateTaskToolExecutor $executor,
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
            'project' => $schema->string(),
            'title' => $schema->string()->required(),
            'description' => $schema->string(),
            'priority' => $schema->string()->enum(Priority::getValues()),
            'due_date' => $schema->string(),
            'assign_to_me' => $schema->boolean(),
            'assigned_to' => $schema->string(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if ($this->organizationId === null) {
            throw new \RuntimeException('Workspace context is required before invoking CreateTaskTool.');
        }

        return json_encode(
            $this->executor->execute($request->all(), $this->organizationId),
            JSON_THROW_ON_ERROR,
        );
    }
}
