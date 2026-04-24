<?php

namespace App\AI\Actions;

use App\AI\Runtime\Contracts\AiStreamOutput;
use App\AI\Runtime\Contracts\AiStreamSink;
use App\AI\Runtime\Enums\AiStreamEvent;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\Streaming\AiStreamEnvelopeFactory;
use App\AI\Runtime\Tools\ToolExecutionResult;
use Symfony\Component\HttpFoundation\Response;

class StreamAiChatAction
{
    public function __construct(
        private readonly AiStreamOutput $streamOutput,
        private readonly AiStreamEnvelopeFactory $streamEnvelopeFactory,
        private readonly BuildAiArtifactsAction $buildAiArtifactsAction,
    ) {}

    public function streamAgentResponse(
        PreparedWorkspaceAssistantRun $preparedRun,
        iterable $stream,
    ): Response {
        return $this->streamOutput->respond(function (AiStreamSink $streamSink) use ($preparedRun, $stream): void {
            $assistantText = '';

            foreach ($stream as $event) {
                $payload = $event->toArray();

                if ($payload === []) {
                    continue;
                }

                $streamSink->publish($payload);

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
                    $streamSink->publish($artifact);
                }
            }

            foreach ($this->buildAiArtifactsAction->resolve($preparedRun, [], $assistantText) as $artifact) {
                $streamSink->publish($artifact->toArray());
            }
        }, $this->metadataFor($preparedRun));
    }

    public function streamManualResponse(
        string $assistantText,
        string $provider,
        ?string $model,
    ): Response {
        return $this->streamOutput->respond(function (AiStreamSink $streamSink) use ($assistantText, $model, $provider): void {
            $streamSink->publish($this->streamEnvelopeFactory->streamStart($provider, $model));
            $streamSink->publish($this->streamEnvelopeFactory->textDelta($assistantText));
            $streamSink->publish($this->streamEnvelopeFactory->streamEnd());
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

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(PreparedWorkspaceAssistantRun $preparedRun): array
    {
        return [
            'session_id' => $preparedRun->context->session?->id,
            'organization_id' => $preparedRun->context->organization->id,
            'user_id' => $preparedRun->context->user->id,
            'provider' => $preparedRun->context->provider,
            'model' => $preparedRun->context->model,
        ];
    }
}
