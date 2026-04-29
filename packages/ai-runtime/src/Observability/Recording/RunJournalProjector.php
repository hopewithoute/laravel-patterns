<?php

namespace Labtime\AiRuntime\Observability\Recording;

use Labtime\AiRuntime\Artifacts\ArtifactStateProjector;
use Labtime\AiRuntime\Observability\Telemetry\RunTelemetryProjector;
use Labtime\AiRuntime\Observability\Telemetry\RunTraceProjector;

class RunJournalProjector
{
    public function __construct(
        private readonly RunTelemetryProjector $runTelemetryProjector,
        private readonly ArtifactStateProjector $artifactStateProjector,
        private readonly RunTraceProjector $runTraceProjector,
    ) {}

    /**
     * @return array{journal: RunJournal, recording: array<string, mixed>, telemetry: array<string, mixed>, artifactState: array<int, array<string, mixed>>, trace: array<int, array<string, mixed>>}
     */
    public function project(RunJournal $journal): array
    {
        return [
            'journal' => $journal,
            'recording' => $journal->toArray(),
            'telemetry' => $this->runTelemetryProjector->project($journal),
            'artifactState' => $this->artifactStateProjector->project($journal),
            'trace' => $this->runTraceProjector->project($journal),
        ];
    }
}
