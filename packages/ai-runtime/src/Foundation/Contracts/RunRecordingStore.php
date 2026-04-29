<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Labtime\AiRuntime\Execution\RuntimeRunReport;

interface RunRecordingStore
{
    public function store(RuntimeRunReport $report): ?object;

    public function driverName(): string;
}
