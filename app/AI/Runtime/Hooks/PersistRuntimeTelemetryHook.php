<?php

namespace App\AI\Runtime\Hooks;

use App\AI\Runtime\Contracts\PostRunHook;
use App\AI\Runtime\Contracts\TelemetryStore;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Hooks\Attributes\RuntimeHook;

#[RuntimeHook(priority: 30)]
readonly class PersistRuntimeTelemetryHook implements PostRunHook
{
    public function __construct(
        private TelemetryStore $telemetryStore,
    ) {}

    public function handle(RuntimeRunReport $report): void
    {
        $this->telemetryStore->store($report);
    }
}
