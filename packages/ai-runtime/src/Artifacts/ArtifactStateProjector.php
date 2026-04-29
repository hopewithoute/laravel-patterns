<?php

namespace Labtime\AiRuntime\Artifacts;

use Labtime\AiRuntime\Observability\Recording\RunJournal;

class ArtifactStateProjector
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function project(RunJournal $journal): array
    {
        /** @var array<string, array<string, mixed>> $artifacts */
        $artifacts = [];
        $anonymousArtifacts = [];

        foreach ($journal->events as $event) {
            if ($event->kind !== 'output.published') {
                continue;
            }

            if (($event->payload['event_type'] ?? null) !== 'artifact') {
                continue;
            }

            $payload = $event->payload['event_payload'] ?? null;

            if (! is_array($payload)) {
                continue;
            }

            $artifactId = $payload['id'] ?? null;

            if (is_string($artifactId) && $artifactId !== '') {
                $artifacts[$artifactId] = $payload;

                continue;
            }

            $anonymousArtifacts[] = $payload;
        }

        return [
            ...array_values($artifacts),
            ...$anonymousArtifacts,
        ];
    }
}
