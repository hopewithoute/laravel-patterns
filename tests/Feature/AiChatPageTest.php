<?php

namespace Tests\Feature;

use App\AI\Agents\WorkspaceAssistantAgent;
use App\Models\AiChatSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiChatPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_user_can_open_the_ai_chat_page_with_sessions_and_messages(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        DB::table('agent_conversations')->insert([
            'id' => 'conversation-001',
            'user_id' => $user->id,
            'title' => 'Release planning',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(4),
        ]);

        DB::table('agent_conversation_messages')->insert([
            [
                'id' => 'message-001',
                'conversation_id' => 'conversation-001',
                'user_id' => $user->id,
                'agent' => class_basename(WorkspaceAssistantAgent::class),
                'role' => 'user',
                'content' => 'Create a release checklist.',
                'attachments' => '[]',
                'tool_calls' => '[]',
                'tool_results' => '[]',
                'usage' => '[]',
                'meta' => '[]',
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
            ],
            [
                'id' => 'message-002',
                'conversation_id' => 'conversation-001',
                'user_id' => $user->id,
                'agent' => class_basename(WorkspaceAssistantAgent::class),
                'role' => 'assistant',
                'content' => 'I can prepare that release checklist.',
                'attachments' => '[]',
                'tool_calls' => json_encode([
                    ['id' => 'call-001', 'name' => 'CreateTaskTool', 'arguments' => ['title' => 'Release checklist']],
                ], JSON_THROW_ON_ERROR),
                'tool_results' => json_encode([
                    ['id' => 'call-001', 'name' => 'CreateTaskTool', 'arguments' => ['title' => 'Release checklist'], 'result' => '{"task_id":"task-001"}'],
                ], JSON_THROW_ON_ERROR),
                'usage' => json_encode(['input_tokens' => 12], JSON_THROW_ON_ERROR),
                'meta' => json_encode([
                    'model' => 'gpt-5.4-mini',
                    'artifacts' => [[
                        'type' => 'artifact',
                        'artifactType' => 'task_summary',
                        'version' => 1,
                        'id' => 'task-001',
                        'title' => 'Task created',
                        'data' => [
                            'task_id' => 'task-001',
                            'project_id' => 'project-001',
                            'title' => 'Release checklist',
                            'status' => 'open',
                        ],
                    ]],
                ], JSON_THROW_ON_ERROR),
                'created_at' => now()->subMinutes(4),
                'updated_at' => now()->subMinutes(4),
            ],
        ]);

        $olderSession = AiChatSession::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'conversation_id' => 'conversation-001',
            'title' => 'Release planning',
            'last_message_at' => now()->subMinutes(4),
            'updated_at' => now()->subMinutes(4),
        ]);
        $newerSession = AiChatSession::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'conversation_id' => null,
            'title' => 'New chat',
            'last_message_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('ai.index', ['session' => $olderSession->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Index')
            ->where('workspace.id', $organization->id)
            ->where('workspace.name', $organization->name)
            ->where('role', 'admin')
            ->where('activeSessionId', $olderSession->id)
            ->where('availableTools.0', 'create_task')
            ->where('availableTools.1', 'lookup_projects')
            ->where('availableTools.2', 'lookup_workspace_users')
            ->where('availableArtifactModes.0.value', 'auto')
            ->where('availableArtifactModes.1.value', 'task_summary')
            ->where('availableArtifactModes.2.value', 'approval_card')
            ->where('availableArtifactModes.3.value', 'stats_card')
            ->has('sessions', 2)
            ->has('messages', 2)
            ->where('messages.0.role', 'user')
            ->where('messages.1.role', 'assistant')
            ->where('messages.1.artifacts.0.artifactType', 'task_summary')
            ->where('messages.1.artifacts.0.data.task_id', 'task-001')
            ->where('messages.1.tool_calls.0.name', 'CreateTaskTool')
            ->where('messages.1.tool_results.0.result', '{"task_id":"task-001"}')
        );

        $this->assertNotSame($olderSession->id, $newerSession->id);
    }

    public function test_ai_chat_page_only_shows_current_user_sessions_in_the_active_workspace(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $visibleSession = AiChatSession::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'conversation_id' => null,
        ]);

        $secondaryOrganization = Organization::factory()->create();
        $secondaryOrganization->members()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        AiChatSession::factory()->create([
            'organization_id' => $secondaryOrganization->id,
            'user_id' => $user->id,
            'conversation_id' => null,
        ]);

        $anotherUser = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'another-user@example.com',
        ]);
        $organization->members()->attach($anotherUser->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        AiChatSession::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $anotherUser->id,
            'conversation_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('ai.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Index')
            ->has('sessions', 1)
            ->where('sessions.0.id', $visibleSession->id)
        );
    }

    public function test_request_without_workspace_context_is_redirected_to_workspace_selection(): void
    {
        [$user] = $this->createWorkspaceUser();

        $response = $this
            ->actingAs($user)
            ->get(route('ai.index'));

        $response->assertRedirect(route('workspace.select'));
    }

    public function test_request_with_stale_workspace_context_is_forbidden(): void
    {
        [$user] = $this->createWorkspaceUser();

        $this
            ->actingAs($user)
            ->withSession(['organization_id' => (string) fake()->uuid()])
            ->get(route('ai.index'))
            ->assertForbidden();
    }
}
