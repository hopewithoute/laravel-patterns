<?php

namespace App\AI\Actions;

use App\AI\Http\SseStreamWriter;
use App\AI\Runtime\Enums\AiStreamEvent;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\Tools\ToolExecutionResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamAiChatAction
{
    public function __construct(
        private readonly SseStreamWriter $sse,
        private readonly BuildAiArtifactsAction $buildAiArtifactsAction,
    ) {}

    public function streamAgentResponse(
        PreparedWorkspaceAssistantRun $preparedRun,
        iterable $stream,
    ): StreamedResponse {
        return $this->sse->stream(function (SseStreamWriter $sse) use ($preparedRun, $stream): void {
            $assistantText = '';

            foreach ($stream as $event) {
                $payload = $event->toArray();

                if ($payload === []) {
                    continue;
                }

                $sse->writePayload($payload);

                $type = AiStreamEvent::tryFrom($payload['type'] ?? '');
                $assistantText .= $this->assistantTextDelta($type, $payload);

                if ($type !== AiStreamEvent::ToolResult) {
                    continue;
                }

                $artifact = $this->buildAiArtifactsAction->resolveOne(
                    $preparedRun,
                    $this->toolExecutionResultFromPayload($preparedRun, $payload),
                )?->toArray();

                if ($artifact !== null) {
                    $sse->writePayload($artifact);
                }
            }

            foreach ($this->buildAiArtifactsAction->resolve($preparedRun, [], $assistantText) as $artifact) {
                $sse->writePayload($artifact->toArray());
            }
        });
    }

    public function streamManualResponse(
        string $assistantText,
        ?string $provider,
        ?string $model,
    ): StreamedResponse {
        return $this->sse->stream(function (SseStreamWriter $sse) use ($assistantText, $model, $provider): void {
            $sse->writePayload([
                'type' => AiStreamEvent::StreamStart->value,
                'provider' => $provider ?? config('ai.default'),
                'model' => $model ?? 'provider-default',
            ]);
            $sse->writePayload([
                'type' => AiStreamEvent::TextDelta->value,
                'delta' => $assistantText,
            ]);
            $sse->writePayload([
                'type' => AiStreamEvent::StreamEnd->value,
                'usage' => [],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assistantTextDelta(?AiStreamEvent $type, array $payload): string
    {
        if ($type !== AiStreamEvent::TextDelta) {
            return '';
        }

        return (string) ($payload['delta'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function toolExecutionResultFromPayload(PreparedWorkspaceAssistantRun $preparedRun, array $payload): ToolExecutionResult
    {
        return $preparedRun->toolExecutionJournal?->next()
            ?? ToolExecutionResult::success(
                toolName: (string) ($payload['tool_name'] ?? 'Tool'),
                result: $payload['result'] ?? null,
                metadata: ['tool_id' => (string) ($payload['tool_id'] ?? '')],
            );
    }
}
