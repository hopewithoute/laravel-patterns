<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Labtime\AiRuntime\Execution\RuntimeRunReport;

interface PostRunHook
{
    public function handle(RuntimeRunReport $report): void;
}
