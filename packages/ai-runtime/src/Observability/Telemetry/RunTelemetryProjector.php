<?php

namespace Labtime\AiRuntime\Observability\Telemetry;

use Labtime\AiRuntime\Observability\Recording\RunJournal;

class RunTelemetryProjector
{
    /**
     * @return array<string, mixed>
     */
    public function project(RunJournal $journal): array
    {
        /** @var array<string, array{operation: string, offset_ms: int, payload: array<string, mixed>}> $startedCalls */
        $startedCalls = [];
        $callCount = 0;
        $failedCallCount = 0;
        $toolCount = 0;
        $toolFailedCount = 0;
        $toolNames = [];
        $outputEventCount = 0;
        $firstTokenLatencyMs = null;
        $failedOperation = null;
        $decisionLatencyMs = null;
        $retrievalLatencyMs = null;
        $providerLatencyMs = null;
        $runLatencyMs = null;

        foreach ($journal->events as $event) {
            if ($event->kind === 'call.started' && $event->callId !== null && $event->operation !== null) {
                $callCount++;
                $startedCalls[$event->callId] = [
                    'operation' => $event->operation,
                    'offset_ms' => $event->offsetMs,
                    'payload' => $event->payload,
                ];

                if ($event->operation === 'tool.execute') {
                    $toolName = $event->payload['input_meta']['tool_name'] ?? null;

                    if (is_string($toolName) && $toolName !== '') {
                        $toolNames[] = $toolName;
                    }
                }

                continue;
            }

            if ($event->kind === 'output.published') {
                $outputEventCount++;

                if ($firstTokenLatencyMs === null && ($event->payload['event_type'] ?? null) === 'text_delta') {
                    $firstTokenLatencyMs = $event->offsetMs;
                }

                continue;
            }

            if ($event->kind === 'call.finished' && $event->callId !== null) {
                $startedCall = $startedCalls[$event->callId] ?? null;

                if (! is_array($startedCall)) {
                    continue;
                }

                $duration = $event->offsetMs - (int) $startedCall['offset_ms'];
                $operation = $startedCall['operation'];

                if ($operation === 'decision' && $decisionLatencyMs === null) {
                    $decisionLatencyMs = $duration;
                }

                if ($operation === 'retrieval' && $retrievalLatencyMs === null) {
                    $retrievalLatencyMs = $duration;
                }

                if ($operation === 'provider.stream' && $providerLatencyMs === null) {
                    $providerLatencyMs = $duration;
                }

                if ($operation === 'tool.execute') {
                    $toolCount++;

                    if ($event->status === 'failed') {
                        $toolFailedCount++;
                    }
                }

                if ($event->status === 'failed') {
                    $failedCallCount++;
                    $failedOperation ??= $operation;
                }

                continue;
            }

            if ($event->kind === 'run.finished') {
                $runLatencyMs = $event->offsetMs;
            }
        }

        return [
            'run_latency_ms' => $runLatencyMs,
            'decision_latency_ms' => $decisionLatencyMs,
            'retrieval_latency_ms' => $retrievalLatencyMs,
            'provider_latency_ms' => $providerLatencyMs,
            'first_token_latency_ms' => $firstTokenLatencyMs,
            'call_count' => $callCount,
            'failed_call_count' => $failedCallCount,
            'tool_count' => $toolCount,
            'tool_failed_count' => $toolFailedCount,
            'tool_names' => array_values(array_unique($toolNames)),
            'output_event_count' => $outputEventCount,
            'failed_operation' => $failedOperation,
        ];
    }
}
