<?php

namespace Labtime\AiRuntime\Observability\Telemetry;

use Labtime\AiRuntime\Execution\RuntimeRunReport;
use Labtime\AiRuntime\Foundation\Contracts\TelemetryStore;

class NullTelemetryStore implements TelemetryStore
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
