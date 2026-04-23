<?php

namespace Tests\Feature;

use App\AI\Agents\WorkspaceAssistantAgent;
use App\Models\AiChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiChatPagePayloadNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_non_list_tool_payloads_from_persisted_messages(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        DB::table('agent_conversations')->insert([
            'id' => 'conversation-legacy',
            'user_id' => $user->id,
            'title' => 'Legacy chat',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(4),
        ]);

        DB::table('agent_conversation_messages')->insert([
            'id' => 'message-legacy',
            'conversation_id' => 'conversation-legacy',
            'user_id' => $user->id,
            'agent' => class_basename(WorkspaceAssistantAgent::class),
            'role' => 'assistant',
            'content' => 'Legacy tool output.',
            'attachments' => '[]',
            'tool_calls' => json_encode([
                'id' => 'call-legacy',
                'name' => 'CreateTaskTool',
                'arguments' => ['title' => 'Legacy task'],
            ], JSON_THROW_ON_ERROR),
            'tool_results' => json_encode([
                'id' => 'call-legacy',
                'name' => 'CreateTaskTool',
                'result' => '{"task_id":"task-legacy"}',
            ], JSON_THROW_ON_ERROR),
            'usage' => json_encode(['input_tokens' => 8], JSON_THROW_ON_ERROR),
            'meta' => json_encode(['artifacts' => []], JSON_THROW_ON_ERROR),
            'created_at' => now()->subMinutes(4),
            'updated_at' => now()->subMinutes(4),
        ]);

        $session = AiChatSession::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'conversation_id' => 'conversation-legacy',
            'title' => 'Legacy chat',
            'last_message_at' => now()->subMinutes(4),
            'updated_at' => now()->subMinutes(4),
        ]);

        $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('ai.index', ['session' => $session->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ai/Index')
                ->where('messages.0.tool_calls.0.name', 'CreateTaskTool')
                ->where('messages.0.tool_results.0.result', '{"task_id":"task-legacy"}')
                ->where('messages.0.usage.input_tokens', 8)
            );
    }
}
