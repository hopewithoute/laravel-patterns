<?php

namespace App\AI\Runtime\Telemetry;

use App\AI\Runtime\Contracts\TelemetryStore;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\Models\AiRuntimeTelemetryRun;

class NullTelemetryStore implements TelemetryStore
{
    public function store(RuntimeRunReport $report): ?AiRuntimeTelemetryRun
    {
        return null;
    }

    public function driverName(): string
    {
        return 'null';
    }
}
