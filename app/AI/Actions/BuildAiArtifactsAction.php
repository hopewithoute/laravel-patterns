<?php

namespace App\AI\Actions;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Artifacts\WorkspaceArtifactResolver;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\Tools\ToolExecutionResult;
use Laravel\Ai\Responses\Data\ToolResult;

readonly class BuildAiArtifactsAction
{
    public function __construct(
        private WorkspaceArtifactResolver $artifactResolver,
    ) {}

    /**
     * @param  iterable<ToolResult|ToolExecutionResult|array<string, mixed>>  $toolResults
     * @return array<int, ArtifactPayload>
     */
    public function resolve(
        PreparedWorkspaceAssistantRun $preparedRun,
        iterable $toolResults,
        ?string $assistantText = null,
    ): array {
        $mappedResults = [];

        foreach ($toolResults as $result) {
            $mappedResults[] = match (true) {
                $result instanceof ToolExecutionResult => $result,
                $result instanceof ToolResult => $this->mapToolResult($result),
                is_array($result) => $this->mapToolResultPayload($result),
                default => null,
            };
        }

        return $this->artifactResolver->resolve(
            context: $preparedRun->context,
            decision: $preparedRun->decision,
            toolResults: array_filter($mappedResults),
            retrievalResult: $preparedRun->retrievalResult,
            assistantText: $assistantText,
        );
    }

    public function resolveOne(
        PreparedWorkspaceAssistantRun $preparedRun,
        ToolResult|ToolExecutionResult|array $toolResult,
        ?string $assistantText = null,
    ): ?ArtifactPayload {
        return $this->resolve($preparedRun, [$toolResult], $assistantText)[0] ?? null;
    }

    private function mapToolResult(ToolResult $toolResult): ToolExecutionResult
    {
        return ToolExecutionResult::success(
            toolName: $toolResult->name,
            input: is_array($toolResult->arguments) ? $toolResult->arguments : [],
            result: $toolResult->result,
            metadata: ['tool_id' => $toolResult->id],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapToolResultPayload(array $payload): ?ToolExecutionResult
    {
        if (($payload['type'] ?? null) !== 'tool_result' || ! ($payload['successful'] ?? false)) {
            return null;
        }

        return ToolExecutionResult::success(
            toolName: (string) ($payload['tool_name'] ?? 'Tool'),
            input: is_array($payload['input'] ?? null) ? $payload['input'] : [],
            result: $payload['result'] ?? null,
            metadata: ['tool_id' => (string) ($payload['tool_id'] ?? '')],
        );
    }
}
