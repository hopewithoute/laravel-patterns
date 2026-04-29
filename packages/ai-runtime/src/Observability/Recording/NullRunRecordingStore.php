<?php

namespace Labtime\AiRuntime\Observability\Recording;

use Labtime\AiRuntime\Execution\RuntimeRunReport;
use Labtime\AiRuntime\Foundation\Contracts\RunRecordingStore;

class NullRunRecordingStore implements RunRecordingStore
{
    public function store(RuntimeRunReport $report): ?object
    {
        return null;
    }

    public function driverName(): string
    {
        return 'null';
    }
}
