<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Tools\Registry\InMemoryToolRegistry;
use App\AI\Runtime\Tools\Registry\ToolDefinition;
use App\AI\Runtime\Tools\WorkspaceToolAccessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceToolAccessResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_returns_uncapable_tools_when_no_explicit_capabilities_exist(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );

        $resolver = new WorkspaceToolAccessResolver(new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'FakeWriteTool',
                uiIdentifier: 'fake_write',
                label: 'Fake write',
                description: 'Fake write tool.',
                whenToUse: 'Use for writes.',
                whenNotToUse: 'Do not use for reads.',
                schemaBuilder: fn ($schema): array => [],
                capability: 'task.create',
                toolClass: FakeWriteTool::class,
            ),
            new ToolDefinition(
                name: 'FakeReadTool',
                uiIdentifier: 'fake_read',
                label: 'Fake read',
                description: 'Fake read tool.',
                whenToUse: 'Use for reads.',
                whenNotToUse: 'Do not use for writes.',
                schemaBuilder: fn ($schema): array => [],
                toolClass: FakeReadTool::class,
            ),
        ]));
        $allowedTools = $resolver->resolve(
            $context,
            PreflightDecision::allow(),
            [new FakeWriteTool, new FakeReadTool],
        );
        $restrictedTools = $resolver->resolve(
            $context,
            PreflightDecision::allow(allowedCapabilities: ['task.create']),
            [new FakeWriteTool, new FakeReadTool],
        );

        $this->assertCount(1, $allowedTools);
        $this->assertInstanceOf(FakeReadTool::class, $allowedTools[0]);
        $this->assertCount(1, $restrictedTools);
        $this->assertInstanceOf(FakeWriteTool::class, $restrictedTools[0]);
    }

    public function test_it_filters_out_all_capability_tools_for_plain_chat_allowlists(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Hello.',
        );

        $resolver = new WorkspaceToolAccessResolver(new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'FakeWriteTool',
                uiIdentifier: 'fake_write',
                label: 'Fake write',
                description: 'Fake write tool.',
                whenToUse: 'Use for writes.',
                whenNotToUse: 'Do not use for reads.',
                schemaBuilder: fn ($schema): array => [],
                capability: 'task.create',
                toolClass: FakeWriteTool::class,
            ),
        ]));

        $tools = $resolver->resolve(
            $context,
            PreflightDecision::allow(allowedCapabilities: ['workspace.read']),
            [new FakeWriteTool],
        );

        $this->assertSame([], $tools);
    }

    public function test_it_can_still_filter_tools_when_a_policy_explicitly_limits_capabilities(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );

        $resolver = new WorkspaceToolAccessResolver(new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'FakeWriteTool',
                uiIdentifier: 'fake_write',
                label: 'Fake write',
                description: 'Fake write tool.',
                whenToUse: 'Use for writes.',
                whenNotToUse: 'Do not use for reads.',
                schemaBuilder: fn ($schema): array => [],
                capability: 'task.create',
                toolClass: FakeWriteTool::class,
            ),
            new ToolDefinition(
                name: 'FakeReadTool',
                uiIdentifier: 'fake_read',
                label: 'Fake read',
                description: 'Fake read tool.',
                whenToUse: 'Use for reads.',
                whenNotToUse: 'Do not use for writes.',
                schemaBuilder: fn ($schema): array => [],
                capability: 'task.read',
                toolClass: FakeReadTool::class,
            ),
        ]));

        $restrictedTools = $resolver->resolve(
            $context,
            PreflightDecision::allow(allowedCapabilities: ['task.create']),
            [new FakeWriteTool, new FakeReadTool],
        );

        $this->assertCount(1, $restrictedTools);
        $this->assertInstanceOf(FakeWriteTool::class, $restrictedTools[0]);
    }
}
class FakeWriteTool {}

class FakeReadTool {}
