<?php

namespace App\AI\Runtime\Artifacts\Builders;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Artifacts\Attributes\RuntimeArtifactType;
use App\AI\Runtime\Artifacts\DecodesToolResults;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Tools\ToolExecutionResult;
use App\AI\Tools\CreateTaskTool;
use Illuminate\Support\Arr;

#[RuntimeArtifactType(
    type: 'approval_card',
    label: 'Approval card',
    description: 'Shows an approval-style card for a completed workspace action.',
    renderer: 'approval-card',
    llmUsageGuidance: 'Use when the user wants confirmation, approval, or review framing.',
    requiredDataKeys: ['headline', 'summary', 'status', 'fields'],
    presentationContract: 'Provide headline, summary, status, fields[], and optional next_step for approval presentation.',
    defaultIntent: ArtifactIntent::ApprovalCard,
    validatorMethod: 'validateApprovalCardData',
)]
#[RuntimeArtifactType(
    type: 'stats_card',
    label: 'Stats card',
    description: 'Shows compact metrics for a task or workspace event.',
    renderer: 'stats-card',
    llmUsageGuidance: 'Use when the user asks for stats, metrics, or quantitative breakdowns.',
    requiredDataKeys: ['items'],
    presentationContract: 'Provide items[] where each item includes a label and value, with optional caption.',
    defaultIntent: ArtifactIntent::StatsCard,
    validatorMethod: 'validateStatsCardData',
)]
#[RuntimeArtifactType(
    type: 'task_summary',
    label: 'Task summary',
    description: 'Summarizes a created task.',
    renderer: 'task-summary',
    llmUsageGuidance: 'Use for concise confirmation after a task is created.',
    requiredDataKeys: ['task_id', 'project_id', 'title', 'status'],
    presentationContract: 'Provide task_id, project_id, project_name, title, status, assigned_to, and assigned_to_name for a created task summary card.',
    defaultIntent: ArtifactIntent::TaskSummary,
    validatorMethod: 'validateTaskSummaryData',
)]
class CreateTaskArtifactBuilder implements ArtifactBuilder
{
    use DecodesToolResults;

    public function supports(string $toolName): bool
    {
        return $toolName === CreateTaskTool::class
            || $toolName === class_basename(CreateTaskTool::class);
    }

    public function build(ToolExecutionResult $result, ArtifactIntent $intent): ?ArtifactPayload
    {
        $decodedResult = $this->decodeResult($result->result);

        if (! $this->canBuildTaskSummary($decodedResult)) {
            return null;
        }

        $toolId = $this->resolveToolId($result);
        $toolName = $result->toolName;

        return match ($intent) {
            ArtifactIntent::ApprovalCard => $this->buildApprovalCardArtifact($toolId, $decodedResult, $toolName),
            ArtifactIntent::StatsCard => $this->buildStatsCardArtifact($toolId, $decodedResult, $toolName),
            default => $this->buildTaskSummaryArtifact($toolId, $decodedResult, $toolName),
        };
    }

    private function canBuildTaskSummary(mixed $decodedResult): bool
    {
        return is_array($decodedResult)
            && is_string(Arr::get($decodedResult, 'task_id'))
            && is_string(Arr::get($decodedResult, 'title'))
            && is_string(Arr::get($decodedResult, 'status'));
    }

    private function buildTaskSummaryArtifact(string $toolId, mixed $decodedResult, string $toolName): ArtifactPayload
    {
        $data = is_array($decodedResult) ? $decodedResult : [];

        return new ArtifactPayload(
            intent: ArtifactIntent::TaskSummary,
            type: 'task_summary',
            id: (string) Arr::get($data, 'task_id', $toolId),
            title: 'Task created',
            data: [
                'task_id' => Arr::get($data, 'task_id'),
                'project_id' => Arr::get($data, 'project_id'),
                'project_name' => Arr::get($data, 'project_name'),
                'title' => Arr::get($data, 'title'),
                'status' => Arr::get($data, 'status'),
                'assigned_to' => Arr::get($data, 'assigned_to'),
                'assigned_to_name' => Arr::get($data, 'assigned_to_name'),
            ],
            meta: [
                'tool_id' => $toolId,
                'tool_name' => $toolName,
                'source' => 'runtime_artifact_resolver',
            ],
        );
    }

    private function buildApprovalCardArtifact(string $toolId, mixed $decodedResult, string $toolName): ArtifactPayload
    {
        $data = is_array($decodedResult) ? $decodedResult : [];

        return new ArtifactPayload(
            intent: ArtifactIntent::ApprovalCard,
            type: 'approval_card',
            id: (string) Arr::get($data, 'task_id', $toolId),
            title: 'Task approval snapshot',
            data: [
                'headline' => Arr::get($data, 'title', 'Task created'),
                'summary' => 'The requested task was created in the active workspace and is ready for the next workflow step.',
                'status' => 'approved',
                'fields' => [
                    ['label' => 'Task ID', 'value' => Arr::get($data, 'task_id')],
                    ['label' => 'Project', 'value' => Arr::get($data, 'project_name', Arr::get($data, 'project_id'))],
                    ['label' => 'Assignee', 'value' => Arr::get($data, 'assigned_to_name', Arr::get($data, 'assigned_to', 'Unassigned'))],
                    ['label' => 'Priority', 'value' => Arr::get($data, 'priority')],
                    ['label' => 'Status', 'value' => Arr::get($data, 'status')],
                ],
                'next_step' => 'Review assignee, due date, and downstream dependencies before execution.',
            ],
            meta: [
                'tool_id' => $toolId,
                'tool_name' => $toolName,
                'source' => 'runtime_artifact_resolver',
            ],
        );
    }

    private function buildStatsCardArtifact(string $toolId, mixed $decodedResult, string $toolName): ArtifactPayload
    {
        $data = is_array($decodedResult) ? $decodedResult : [];

        return new ArtifactPayload(
            intent: ArtifactIntent::StatsCard,
            type: 'stats_card',
            id: (string) Arr::get($data, 'task_id', $toolId),
            title: 'Task metrics',
            data: [
                'items' => [
                    ['label' => 'Status', 'value' => Arr::get($data, 'status'), 'caption' => 'Initial workflow state'],
                    ['label' => 'Priority', 'value' => Arr::get($data, 'priority', 'medium'), 'caption' => 'Requested execution priority'],
                    ['label' => 'Due Date', 'value' => Arr::get($data, 'due_date', 'Not set'), 'caption' => 'Current delivery target'],
                ],
            ],
            meta: [
                'tool_id' => $toolId,
                'tool_name' => $toolName,
                'source' => 'runtime_artifact_resolver',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateApprovalCardData(array $data): bool
    {
        return is_string($data['headline'] ?? null)
            && is_string($data['summary'] ?? null)
            && is_string($data['status'] ?? null)
            && is_array($data['fields'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateStatsCardData(array $data): bool
    {
        return is_array($data['items'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateTaskSummaryData(array $data): bool
    {
        return is_string($data['task_id'] ?? null)
            && is_string($data['title'] ?? null)
            && is_string($data['status'] ?? null);
    }
}
