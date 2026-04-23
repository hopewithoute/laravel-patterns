<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Execution\RuntimeRunReport;

interface PostRunHook
{
    public function handle(RuntimeRunReport $report): void;
}
