<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Execution\RuntimeRunReport;
use App\Models\AiRuntimeTelemetryRun;

interface TelemetryStore
{
    public function store(RuntimeRunReport $report): ?AiRuntimeTelemetryRun;

    public function driverName(): string;
}
