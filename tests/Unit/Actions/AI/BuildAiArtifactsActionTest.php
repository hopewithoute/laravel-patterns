<?php

namespace Tests\Unit\Actions\AI;

use App\AI\Actions\BuildAiArtifactsAction;
use App\AI\Runtime\Artifacts\WorkspaceArtifactResolver;
use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Preflight\WorkspacePromptClassifier;
use App\AI\Runtime\Retrieval\RetrievalResult;
use App\AI\Runtime\Tools\ToolExecutionResult;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Responses\Data\ToolResult;
use Tests\TestCase;

class BuildAiArtifactsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_task_summary_artifact_for_create_task_results(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(), new ToolResult(
            id: 'call-001',
            name: 'CreateTaskTool',
            arguments: ['title' => 'Release checklist'],
            result: json_encode([
                'task_id' => 'task-001',
                'project_id' => 'project-001',
                'project_name' => 'Website Redesign',
                'title' => 'Release checklist',
                'status' => 'open',
            ], JSON_THROW_ON_ERROR),
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('artifact', $artifact['type']);
        $this->assertSame('task_summary', $artifact['artifactType']);
        $this->assertSame('task-001', $artifact['id']);
        $this->assertSame('Release checklist', $artifact['data']['title']);
        $this->assertSame('Website Redesign', $artifact['data']['project_name']);
        $this->assertSame('task-summary', $artifact['meta']['renderer']);
    }

    public function test_it_passes_through_explicit_artifact_payloads(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(), new ToolResult(
            id: 'call-002',
            name: 'ArtifactTool',
            arguments: [],
            result: json_encode([
                'type' => 'artifact',
                'artifactType' => 'table',
                'version' => 1,
                'id' => 'artifact-001',
                'title' => 'Tasks',
                'data' => [
                    'columns' => ['Task', 'Status'],
                    'rows' => [['Release checklist', 'open']],
                ],
            ], JSON_THROW_ON_ERROR),
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('table', $artifact['artifactType']);
        $this->assertSame('artifact-001', $artifact['id']);
        $this->assertSame('Tasks', $artifact['title']);
        $this->assertSame('auto', $artifact['intent']);
        $this->assertSame('table', $artifact['meta']['renderer']);
    }

    public function test_it_falls_back_when_explicit_artifact_payload_is_not_registered_or_invalid(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(), new ToolResult(
            id: 'call-002b',
            name: 'ArtifactTool',
            arguments: [],
            result: json_encode([
                'type' => 'artifact',
                'artifactType' => 'unknown_chart',
                'version' => 1,
                'id' => 'artifact-unknown',
                'title' => 'Unknown',
                'data' => [
                    'foo' => 'bar',
                ],
            ], JSON_THROW_ON_ERROR),
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('json_fallback', $artifact['artifactType']);
        $this->assertSame('JSON fallback', $artifact['title']);
        $this->assertSame('json-fallback', $artifact['meta']['renderer']);
    }

    public function test_it_builds_a_json_fallback_artifact_for_unknown_tools(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(), new ToolResult(
            id: 'call-003',
            name: 'UnknownTool',
            arguments: [],
            result: 'plain text result',
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('json_fallback', $artifact['artifactType']);
        $this->assertSame('JSON fallback', $artifact['title']);
        $this->assertSame('plain text result', $artifact['data']['result']);
        $this->assertSame('json-fallback', $artifact['meta']['renderer']);
    }

    public function test_it_only_emits_artifacts_for_successful_tool_result_stream_events(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(), [
            'type' => 'tool_result',
            'tool_id' => 'call-004',
            'tool_name' => 'CreateTaskTool',
            'result' => '{"task_id":"task-001","project_id":"project-001","title":"Release checklist","status":"open"}',
            'successful' => true,
        ])?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('task_summary', $artifact['artifactType']);
        $this->assertSame('task-summary', $artifact['meta']['renderer']);
        $this->assertNull($action->resolveOne($this->preparedRun(), [
            'type' => 'tool_result',
            'tool_id' => 'call-005',
            'tool_name' => 'CreateTaskTool',
            'result' => '{}',
            'successful' => false,
        ]));
    }

    public function test_auto_mode_selects_approval_card_when_prompt_requests_review_or_approval(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(prompt: 'Create the task and show me an approval review card.'), new ToolResult(
            id: 'call-005a',
            name: 'CreateTaskTool',
            arguments: [],
            result: json_encode([
                'task_id' => 'task-001',
                'project_id' => 'project-001',
                'title' => 'Review customer escalation',
                'status' => 'todo',
                'priority' => 'high',
                'due_date' => '2026-04-30',
            ], JSON_THROW_ON_ERROR),
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('approval_card', $artifact['artifactType']);
        $this->assertSame('approval_card', $artifact['intent']);
        $this->assertSame('approval-card', $artifact['meta']['renderer']);
    }

    public function test_auto_mode_selects_stats_card_when_prompt_requests_metrics(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(prompt: 'Create the task and give me stats and metrics.'), new ToolResult(
            id: 'call-005b',
            name: 'CreateTaskTool',
            arguments: [],
            result: json_encode([
                'task_id' => 'task-001',
                'project_id' => 'project-001',
                'title' => 'Review customer escalation',
                'status' => 'todo',
                'priority' => 'high',
                'due_date' => '2026-04-30',
            ], JSON_THROW_ON_ERROR),
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('stats_card', $artifact['artifactType']);
        $this->assertSame('stats_card', $artifact['intent']);
        $this->assertSame('stats-card', $artifact['meta']['renderer']);
    }

    public function test_it_can_build_an_approval_card_for_create_task_results(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(artifactIntent: ArtifactIntent::ApprovalCard), new ToolResult(
            id: 'call-006',
            name: 'CreateTaskTool',
            arguments: [],
            result: json_encode([
                'task_id' => 'task-001',
                'project_id' => 'project-001',
                'title' => 'Review customer escalation',
                'status' => 'todo',
                'priority' => 'high',
                'due_date' => '2026-04-30',
            ], JSON_THROW_ON_ERROR),
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('approval_card', $artifact['artifactType']);
        $this->assertSame('approved', $artifact['data']['status']);
        $this->assertSame('Review customer escalation', $artifact['data']['headline']);
        $this->assertSame('approval-card', $artifact['meta']['renderer']);
    }

    public function test_it_can_build_a_stats_card_for_create_task_results(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne($this->preparedRun(artifactIntent: ArtifactIntent::StatsCard), new ToolResult(
            id: 'call-007',
            name: 'CreateTaskTool',
            arguments: [],
            result: json_encode([
                'task_id' => 'task-001',
                'project_id' => 'project-001',
                'title' => 'Review customer escalation',
                'status' => 'todo',
                'priority' => 'high',
                'due_date' => '2026-04-30',
            ], JSON_THROW_ON_ERROR),
        ))?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('stats_card', $artifact['artifactType']);
        $this->assertSame('Status', $artifact['data']['items'][0]['label']);
        $this->assertSame('high', $artifact['data']['items'][1]['value']);
        $this->assertSame('stats-card', $artifact['meta']['renderer']);
    }

    public function test_it_does_not_build_artifacts_for_failed_runtime_tool_results(): void
    {
        $action = $this->makeAction();

        $artifact = $action->resolveOne(
            $this->preparedRun(),
            ToolExecutionResult::failure(
                toolName: 'CreateTaskTool',
                input: ['title' => 'Release checklist'],
                error: 'Project is outside the active workspace.',
                failureType: 'authorization_error',
                failureBehavior: 'surface_to_user',
            ),
        )?->toArray();

        $this->assertIsArray($artifact);
        $this->assertSame('key_value', $artifact['artifactType']);
        $this->assertSame('failed', $artifact['data']['status']);
        $this->assertSame('authorization_error', $artifact['data']['failure_type']);
        $this->assertSame('key-value', $artifact['meta']['renderer']);
    }

    public function test_it_does_not_build_retrieval_artifacts_for_barebone_runtime(): void
    {
        $action = $this->makeAction();

        $artifacts = $action->resolve(
            $this->preparedRun(retrievalResult: new RetrievalResult(
                query: 'Summarize the release runbook.',
                metadata: [
                    'summary' => 'Relevant knowledge matches...',
                    'sources' => ['lexical_docs'],
                ],
            )),
            [],
        );

        $this->assertSame([], $artifacts);
    }

    private function makeAction(): BuildAiArtifactsAction
    {
        return new BuildAiArtifactsAction(app(WorkspaceArtifactResolver::class));
    }

    private function preparedRun(
        ?string $prompt = null,
        ArtifactIntent $artifactIntent = ArtifactIntent::Auto,
        ?RetrievalResult $retrievalResult = null,
    ): PreparedWorkspaceAssistantRun {
        $context = new AiRuntimeContext(
            user: new User(['name' => 'Test User']),
            organization: new Organization(['name' => 'Test Workspace']),
            session: null,
            prompt: $prompt ?? 'Create the task.',
            requestedArtifactMode: ArtifactIntent::Auto,
        );

        $decision = PreflightDecision::allow(
            intent: AiIntent::WorkspaceChat,
            allowedCapabilities: ['task.create'],
            artifactIntent: $artifactIntent,
        );

        if ($artifactIntent === ArtifactIntent::Auto && $prompt !== null) {
            $decision = app(WorkspacePromptClassifier::class)->classify($context);
        }

        return new PreparedWorkspaceAssistantRun(
            context: $context,
            decision: $decision,
            instructions: 'Runtime instructions',
            tools: [],
            retrievalResult: $retrievalResult,
        );
    }
}
