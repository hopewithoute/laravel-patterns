<?php

namespace Labtime\AiRuntime\Observability\Telemetry;

use Labtime\AiRuntime\Observability\Recording\RunJournal;

class RunTraceProjector
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function project(RunJournal $journal): array
    {
        $decision = null;
        $retrieval = null;
        $toolCount = 0;
        $toolFailedCount = 0;
        $toolNames = [];
        $artifacts = [];
        $completion = null;

        foreach ($journal->events as $event) {
            if ($event->kind === 'call.finished' && $event->operation === 'decision') {
                $resultMeta = is_array($event->payload['result_meta'] ?? null)
                    ? $event->payload['result_meta']
                    : [];

                $decision = [
                    'stage' => 'decision',
                    'status' => $resultMeta['decision'] ?? $event->status,
                    'intent' => $resultMeta['intent'] ?? null,
                    'risk_level' => $resultMeta['risk_level'] ?? null,
                ];

                continue;
            }

            if ($event->kind === 'call.finished' && $event->operation === 'retrieval') {
                $resultMeta = is_array($event->payload['result_meta'] ?? null)
                    ? $event->payload['result_meta']
                    : [];
                $required = (bool) ($resultMeta['required'] ?? false);

                $retrieval = [
                    'stage' => 'retrieval',
                    'status' => $required ? 'completed' : 'skipped',
                    'sources' => is_array($resultMeta['sources'] ?? null) ? $resultMeta['sources'] : [],
                    'strategy' => $resultMeta['strategy'] ?? null,
                    'documents_count' => (int) ($resultMeta['documents_count'] ?? 0),
                ];

                continue;
            }

            if ($event->kind === 'call.finished' && $event->operation === 'tool.execute') {
                $toolCount++;

                $resultMeta = is_array($event->payload['result_meta'] ?? null)
                    ? $event->payload['result_meta']
                    : [];
                $toolName = $resultMeta['tool_name'] ?? null;

                if (is_string($toolName) && $toolName !== '') {
                    $toolNames[] = $toolName;
                }

                if ($event->status === 'failed') {
                    $toolFailedCount++;
                }

                continue;
            }

            if ($event->kind === 'output.published' && ($event->payload['event_type'] ?? null) === 'artifact') {
                $payload = $event->payload['event_payload'] ?? null;

                if (is_array($payload)) {
                    $artifacts[] = $payload;
                }

                continue;
            }

            if ($event->kind === 'run.finished') {
                $usage = is_array($event->payload['usage'] ?? null)
                    ? $event->payload['usage']
                    : [];
                $resultMeta = is_array($event->payload['result_meta'] ?? null)
                    ? $event->payload['result_meta']
                    : [];

                $completion = [
                    'stage' => 'completion',
                    'status' => $journal->status === 'failed' ? 'failed' : 'completed',
                    'mode' => $event->payload['completion_mode'] ?? null,
                    'provider' => $journal->provider,
                    'model' => $journal->model,
                    'usage' => $usage,
                    'result_meta' => $resultMeta,
                ];
            }
        }

        return [
            $decision ?? [
                'stage' => 'decision',
                'status' => 'unknown',
                'intent' => null,
                'risk_level' => null,
            ],
            $retrieval ?? [
                'stage' => 'retrieval',
                'status' => 'skipped',
                'sources' => [],
                'strategy' => null,
                'documents_count' => 0,
            ],
            [
                'stage' => 'tools',
                'status' => $toolCount === 0 ? 'skipped' : 'completed',
                'count' => $toolCount,
                'failed_count' => $toolFailedCount,
                'tools' => array_values(array_unique($toolNames)),
            ],
            [
                'stage' => 'artifacts',
                'status' => $artifacts === [] ? 'skipped' : 'completed',
                'count' => count($artifacts),
                'types' => array_values(array_unique(array_filter(array_map(
                    fn (array $artifact): ?string => is_string($artifact['artifactType'] ?? null)
                        ? $artifact['artifactType']
                        : null,
                    $artifacts,
                )))),
            ],
            $completion ?? [
                'stage' => 'completion',
                'status' => $journal->status === 'failed' ? 'failed' : 'completed',
                'mode' => null,
                'provider' => $journal->provider,
                'model' => $journal->model,
                'usage' => [],
                'result_meta' => [],
            ],
        ];
    }
}
