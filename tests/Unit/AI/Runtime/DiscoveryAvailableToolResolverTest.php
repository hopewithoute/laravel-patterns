<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Tools\Registry\InMemoryToolRegistry;
use App\AI\Runtime\Tools\Registry\RegistryAvailableToolResolver;
use App\AI\Runtime\Tools\Registry\ToolDefinition;
use App\AI\Runtime\Tools\Registry\ToolPromptCatalogBuilder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Contracts\Tool;
use Mockery;
use Tests\TestCase;

class DiscoveryAvailableToolResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_available_tools_from_classes(): void
    {
        $container = Mockery::mock(Container::class);
        $registry = new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'FakeGlobalTool',
                uiIdentifier: 'fake_global',
                label: 'Fake global',
                description: 'Fake global tool.',
                whenToUse: 'Use for global actions.',
                whenNotToUse: 'Do not use for workspace scoped actions.',
                schemaBuilder: fn ($schema): array => [],
                toolClass: FakeGlobalTool::class,
            ),
        ]);

        $context = AiRuntimeContext::make(
            user: User::factory()->create(),
            organization: Organization::factory()->create(),
            session: null,
            prompt: 'test'
        );

        $container->shouldReceive('make')
            ->with(FakeGlobalTool::class)
            ->andReturn(new FakeGlobalTool);

        $resolver = new RegistryAvailableToolResolver(
            container: $container,
            toolRegistry: $registry,
            toolPromptCatalogBuilder: new ToolPromptCatalogBuilder($registry),
        );

        $tools = $resolver->resolve($context);

        $this->assertCount(1, $tools);
    }

    public function test_it_resolves_workspace_scoped_tools(): void
    {
        $container = Mockery::mock(Container::class);
        $registry = new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'FakeWorkspaceTool',
                uiIdentifier: 'fake_workspace',
                label: 'Fake workspace',
                description: 'Fake workspace tool.',
                whenToUse: 'Use for workspace actions.',
                whenNotToUse: 'Do not use for global actions.',
                schemaBuilder: fn ($schema): array => [],
                scope: 'workspace',
                toolClass: FakeWorkspaceTool::class,
            ),
        ]);

        $context = AiRuntimeContext::make(
            user: User::factory()->create(),
            organization: Organization::factory()->create(),
            session: null,
            prompt: 'test'
        );

        $container->shouldReceive('make')
            ->with(FakeWorkspaceTool::class)
            ->andReturn(new FakeWorkspaceTool);

        $resolver = new RegistryAvailableToolResolver(
            container: $container,
            toolRegistry: $registry,
            toolPromptCatalogBuilder: new ToolPromptCatalogBuilder($registry),
        );

        $tools = $resolver->resolve($context);

        $this->assertCount(1, $tools);
        /** @var mixed $tool */
        $tool = $tools[0];
        $this->assertSame($context->organization->id, $tool->organizationId);
    }

    public function test_it_builds_prompt_instruction_from_tool_classes(): void
    {
        $registry = new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'CreateTaskTool',
                uiIdentifier: 'create_task',
                label: 'Create task',
                description: 'Create a task.',
                whenToUse: 'Use when creating a task.',
                whenNotToUse: 'Do not use for reads.',
                schemaBuilder: fn ($schema): array => [],
                requiredInputs: ['project_id', 'title'],
                outputContract: 'Returns created task fields.',
            ),
            new ToolDefinition(
                name: 'LookupTaskTool',
                uiIdentifier: 'lookup_task',
                label: 'Lookup task',
                description: 'Lookup tasks.',
                whenToUse: 'Use when listing tasks.',
                whenNotToUse: 'Do not use for writes.',
                schemaBuilder: fn ($schema): array => [],
            ),
        ]);

        $resolver = new RegistryAvailableToolResolver(
            container: Mockery::mock(Container::class),
            toolRegistry: $registry,
            toolPromptCatalogBuilder: new ToolPromptCatalogBuilder($registry),
        );

        $this->assertStringContainsString('Available runtime tools: CreateTaskTool, LookupTaskTool.', $resolver->promptInstruction());
        $this->assertStringContainsString('Required fields: project_id, title.', $resolver->promptInstruction());
    }

    public function test_it_builds_ui_identifiers_from_tool_classes(): void
    {
        $registry = new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'CreateTaskTool',
                uiIdentifier: 'create_task',
                label: 'Create task',
                description: 'Create a task.',
                whenToUse: 'Use when creating a task.',
                whenNotToUse: 'Do not use for reads.',
                schemaBuilder: fn ($schema): array => [],
            ),
            new ToolDefinition(
                name: 'LookupTaskTool',
                uiIdentifier: 'lookup_task',
                label: 'Lookup task',
                description: 'Lookup tasks.',
                whenToUse: 'Use when listing tasks.',
                whenNotToUse: 'Do not use for writes.',
                schemaBuilder: fn ($schema): array => [],
            ),
        ]);

        $resolver = new RegistryAvailableToolResolver(
            container: Mockery::mock(Container::class),
            toolRegistry: $registry,
            toolPromptCatalogBuilder: new ToolPromptCatalogBuilder($registry),
        );

        $this->assertSame(['create_task', 'lookup_task'], $resolver->uiIdentifiers());
    }

    public function test_it_returns_a_fallback_message_when_no_tools_are_classes(): void
    {
        $registry = new InMemoryToolRegistry([]);
        $resolver = new RegistryAvailableToolResolver(
            container: Mockery::mock(Container::class),
            toolRegistry: $registry,
            toolPromptCatalogBuilder: new ToolPromptCatalogBuilder($registry),
        );

        $this->assertSame('No runtime tools are currently enabled.', $resolver->promptInstruction());
        $this->assertSame([], $resolver->uiIdentifiers());
    }
}

class FakeGlobalTool implements Tool
{
    public function description(): string
    {
        return '';
    }

    public function schema($schema): array
    {
        return [];
    }

    public function handle($request): string
    {
        return '';
    }
}

use App\AI\Runtime\Contracts\WorkspaceScopedTool;

class FakeWorkspaceTool implements WorkspaceScopedTool
{
    public $organizationId;

    public function forWorkspace(Organization|string $org): self
    {
        $this->organizationId = $org instanceof Organization ? $org->id : $org;

        return $this;
    }

    public function description(): string
    {
        return '';
    }

    public function schema($schema): array
    {
        return [];
    }

    public function handle($request): string
    {
        return '';
    }
}
