<?php

namespace Database\Factories;

use App\Models\AiRuntimeTelemetryRun;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRuntimeTelemetryRun>
 */
class AiRuntimeTelemetryRunFactory extends Factory
{
    protected $model = AiRuntimeTelemetryRun::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'ai_chat_session_id' => null,
            'conversation_id' => fake()->uuid(),
            'assistant_message_id' => fake()->uuid(),
            'intent' => 'knowledge_lookup',
            'decision' => 'allow',
            'risk_level' => 'low',
            'completion_mode' => 'agent_stream',
            'provider' => 'cliproxyapi',
            'model' => 'gpt-4.1',
            'retrieval_strategy' => 'basic_workspace_context',
            'retrieval_required' => true,
            'retrieval_documents_count' => 2,
            'retrieval_sources' => ['lexical_docs', 'vector_docs'],
            'tools_count' => 0,
            'tool_failed_count' => 0,
            'artifacts_count' => 1,
            'prompt_tokens' => 100,
            'completion_tokens' => 40,
            'total_tokens' => 140,
            'preflight_meta' => ['classifier' => 'keyword_rule_based'],
            'tool_summary' => ['count' => 0, 'failed_count' => 0],
            'retrieval_summary' => ['documents_count' => 2],
            'usage' => ['total_tokens' => 140],
            'trace' => [['stage' => 'completion', 'status' => 'completed']],
            'meta' => ['storage_version' => 2],
        ];
    }
}
