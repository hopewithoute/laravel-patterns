<?php

namespace Labtime\AiRuntime\Observability\Replay;

use Labtime\AiRuntime\Foundation\Contracts\AiStreamSink;
use Labtime\AiRuntime\Observability\Recording\RunEvent;
use Labtime\AiRuntime\Observability\Recording\RunJournal;
use Labtime\AiRuntime\Streaming\AiStreamTransportRegistry;
use Symfony\Component\HttpFoundation\Response;

readonly class RunReplayPlayer
{
    public function __construct(
        private AiStreamTransportRegistry $transportRegistry,
    ) {}

    public function replay(RunJournal $journal, ?string $callId = null, ?string $driver = null): Response
    {
        return $this->transportRegistry->resolve($driver)->respond(
            producer: function (AiStreamSink $streamSink) use ($callId, $journal): void {
                foreach ($this->replayPayloads($journal, $callId) as $payload) {
                    $streamSink->publish($payload);
                }
            },
            metadata: $this->metadataFor($journal),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function replayPayloads(RunJournal $journal, ?string $callId = null): array
    {
        $events = array_values(array_filter(
            $journal->events,
            fn (RunEvent $event): bool => $event->kind === 'output.published'
                && ($callId === null || $event->callId === $callId)
                && is_array($event->payload['event_payload'] ?? null),
        ));

        usort($events, fn (RunEvent $left, RunEvent $right): int => $left->sequence <=> $right->sequence);

        return array_values(array_map(
            fn (RunEvent $event): array => $event->payload['event_payload'],
            $events,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(RunJournal $journal): array
    {
        return [
            'session_id' => $journal->sessionId,
            'conversation_id' => $journal->conversationId,
            'provider' => $journal->provider,
            'model' => $journal->model,
            'run_id' => $journal->runId,
        ];
    }
}
