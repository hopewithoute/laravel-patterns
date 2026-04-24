<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Tools\Registry\ToolDefinition;
use App\AI\Runtime\Tools\Registry\ToolRegistry;
use App\AI\Runtime\Tools\WorkspaceToolExecutionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkspaceToolExecutionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_wraps_successful_tool_execution_with_normalized_metadata(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        Log::spy();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );

        $result = app(WorkspaceToolExecutionPolicy::class)->execute(
            context: $context,
            toolName: 'CreateTaskTool',
            input: ['title' => 'Release checklist'],
            next: fn (string $toolName, array $input): string => json_encode([
                'task_id' => 'task-001',
                'title' => $input['title'],
                'status' => 'todo',
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertTrue($result->successful);
        $this->assertSame('write', $result->metadata['operation']);
        $this->assertSame(1, $result->metadata['attempt']);
        $this->assertSame($organization->id, $result->metadata['workspace_id']);
    }

    public function test_it_captures_failures_without_throwing_for_tool_wrappers(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        Log::spy();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );

        $result = app(WorkspaceToolExecutionPolicy::class)->execute(
            context: $context,
            toolName: 'CreateTaskTool',
            input: ['title' => 'Release checklist'],
            next: function (): void {
                throw new \RuntimeException('Project is outside the active workspace.');
            },
        );

        $this->assertFalse($result->successful);
        $this->assertSame('Project is outside the active workspace.', $result->error);
        $this->assertSame(\RuntimeException::class, $result->metadata['exception']);
        $this->assertSame('write', $result->metadata['operation']);
        $this->assertSame('authorization_error', $result->failureType);
        $this->assertSame('surface_to_user', $result->failureBehavior);
    }

    public function test_it_classifies_validation_failures_as_user_fixable_errors(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        Log::spy();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );

        $result = app(WorkspaceToolExecutionPolicy::class)->execute(
            context: $context,
            toolName: 'CreateTaskTool',
            input: ['title' => ''],
            next: function (): void {
                throw ValidationException::withMessages([
                    'title' => 'The title field is required.',
                ]);
            },
        );

        $this->assertFalse($result->successful);
        $this->assertSame('validation_error', $result->failureType);
        $this->assertSame('ask_user', $result->failureBehavior);
    }

    public function test_it_uses_tool_metadata_for_operation_and_retry_attempts(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        Log::spy();

        $this->mock(ToolRegistry::class, function ($mock) {
            $mock->shouldReceive('find')
                ->with('CreateTaskTool')
                ->andReturn(new ToolDefinition(
                    name: 'CreateTaskTool',
                    uiIdentifier: 'create_task',
                    label: 'Create task',
                    description: 'Create a task.',
                    whenToUse: 'Use for creates.',
                    whenNotToUse: 'Do not use for reads.',
                    schemaBuilder: fn ($schema): array => [],
                    operation: 'mutation',
                    maxAttempts: 2,
                ));
        });

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );

        $attempts = 0;

        $result = app(WorkspaceToolExecutionPolicy::class)->execute(
            context: $context,
            toolName: 'CreateTaskTool',
            input: ['title' => 'Release checklist'],
            next: function () use (&$attempts): string {
                $attempts++;

                if ($attempts === 1) {
                    throw new ConnectionException('Temporary upstream failure.');
                }

                return '{"status":"ok"}';
            },
        );

        $this->assertSame(2, $attempts);
        $this->assertTrue($result->successful);
        $this->assertSame('mutation', $result->metadata['operation']);
        $this->assertSame(2, $result->metadata['attempt']);
        $this->assertSame(2, $result->metadata['max_attempts']);
    }

    public function test_it_blocks_task_create_tools_for_non_privileged_workspace_roles(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $organization->members()->updateExistingPivot($user->id, [
            'role' => 'member',
        ]);
        Log::spy();

        $this->mock(ToolRegistry::class, function ($mock) {
            $mock->shouldReceive('find')
                ->with('CreateTaskTool')
                ->andReturn(new ToolDefinition(
                    name: 'CreateTaskTool',
                    uiIdentifier: 'create_task',
                    label: 'Create task',
                    description: 'Create a task.',
                    whenToUse: 'Use for creates.',
                    whenNotToUse: 'Do not use for reads.',
                    schemaBuilder: fn ($schema): array => [],
                    capability: 'task.create',
                    operation: 'write',
                ));
        });

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );
        $executed = false;

        $result = app(WorkspaceToolExecutionPolicy::class)->execute(
            context: $context,
            toolName: 'CreateTaskTool',
            input: ['title' => 'Release checklist'],
            next: function () use (&$executed): string {
                $executed = true;

                return '{"status":"ok"}';
            },
        );

        $this->assertFalse($executed);
        $this->assertFalse($result->successful);
        $this->assertSame('authorization_error', $result->failureType);
        $this->assertSame('surface_to_user', $result->failureBehavior);
    }
}
