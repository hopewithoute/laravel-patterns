<?php

namespace App\AI\Runtime\Artifacts\Builders;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Artifacts\Attributes\RuntimeArtifactType;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Tools\ToolExecutionResult;

#[RuntimeArtifactType(
    type: 'bar_chart',
    label: 'Bar chart',
    description: 'Displays categorical comparison data.',
    renderer: 'bar-chart',
    llmUsageGuidance: 'Use for comparing values across categories.',
    requiredDataKeys: ['series', 'xLabel', 'yLabel'],
    presentationContract: 'Provide series[] plus xLabel and yLabel for categorical chart rendering.',
    validatorMethod: 'validateChartData',
)]
#[RuntimeArtifactType(
    type: 'checklist',
    label: 'Checklist',
    description: 'Displays a sequence of checklist items.',
    renderer: 'checklist',
    llmUsageGuidance: 'Use when the result is best communicated as a checklist with completion states.',
    requiredDataKeys: ['items'],
    presentationContract: 'Provide items[] where each item has label, checked, and optional description.',
    validatorMethod: 'validateChecklistData',
)]
#[RuntimeArtifactType(
    type: 'json_fallback',
    label: 'JSON fallback',
    description: 'Fallback artifact for untyped structured results.',
    renderer: 'json-fallback',
    llmUsageGuidance: 'Use only when no richer artifact type is valid.',
    requiredDataKeys: ['tool_name', 'result'],
    presentationContract: 'Provide tool_name and raw result for fallback inspection.',
)]
#[RuntimeArtifactType(
    type: 'key_value',
    label: 'Key value',
    description: 'Simple key-value output for failures or generic states.',
    renderer: 'key-value',
    llmUsageGuidance: 'Use for blocked actions or simple status output.',
    requiredDataKeys: ['tool', 'status', 'failure_type', 'failure_behavior', 'message'],
    presentationContract: 'Provide tool, status, failure_type, failure_behavior, and message.',
    defaultIntent: ArtifactIntent::None,
    validatorMethod: 'validateKeyValueData',
)]
#[RuntimeArtifactType(
    type: 'line_chart',
    label: 'Line chart',
    description: 'Displays trend data over a sequence.',
    renderer: 'line-chart',
    llmUsageGuidance: 'Use for trends over time or ordered sequences.',
    requiredDataKeys: ['series', 'xLabel', 'yLabel'],
    presentationContract: 'Provide series[] plus xLabel and yLabel for trend chart rendering.',
    validatorMethod: 'validateChartData',
)]
#[RuntimeArtifactType(
    type: 'markdown',
    label: 'Markdown',
    description: 'Displays rich markdown content.',
    renderer: 'markdown',
    llmUsageGuidance: 'Use when long-form explanation or formatted prose is the clearest output.',
    requiredDataKeys: ['content'],
    presentationContract: 'Provide a content string containing markdown-safe text.',
    validatorMethod: 'validateMarkdownData',
)]
#[RuntimeArtifactType(
    type: 'table',
    label: 'Table',
    description: 'Displays tabular structured data.',
    renderer: 'table',
    llmUsageGuidance: 'Use when rows and columns are the clearest presentation format.',
    requiredDataKeys: ['columns', 'rows'],
    presentationContract: 'Provide columns[] and rows[] arrays for tabular rendering.',
    validatorMethod: 'validateTableData',
)]
class GenericRuntimeArtifactBuilder implements ArtifactBuilder
{
    public function supports(string $toolName): bool
    {
        return false;
    }

    public function build(ToolExecutionResult $result, ArtifactIntent $intent): ?ArtifactPayload
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateChartData(array $data): bool
    {
        return is_array($data['series'] ?? null)
            && is_string($data['xLabel'] ?? null)
            && is_string($data['yLabel'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateChecklistData(array $data): bool
    {
        return is_array($data['items'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateKeyValueData(array $data): bool
    {
        return is_string($data['tool'] ?? null)
            && is_string($data['status'] ?? null)
            && is_string($data['failure_type'] ?? null)
            && is_string($data['failure_behavior'] ?? null)
            && is_string($data['message'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateMarkdownData(array $data): bool
    {
        return is_string($data['content'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateTableData(array $data): bool
    {
        return is_array($data['columns'] ?? null)
            && is_array($data['rows'] ?? null);
    }
}
