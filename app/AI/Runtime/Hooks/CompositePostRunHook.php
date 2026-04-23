<?php

namespace App\AI\Runtime\Hooks;

use App\AI\Runtime\Contracts\PostRunHook;
use App\AI\Runtime\Execution\RuntimeRunReport;

readonly class CompositePostRunHook implements PostRunHook
{
    /**
     * @param  iterable<PostRunHook>  $hooks
     */
    public function __construct(
        private iterable $hooks,
    ) {}

    public function handle(RuntimeRunReport $report): void
    {
        foreach ($this->hooks as $hook) {
            try {
                $hook->handle($report);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
