<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Labtime\AiRuntime\Execution\RuntimeRunReport;

interface TelemetryStore
{
    public function store(RuntimeRunReport $report): ?object;

    public function driverName(): string;
}
