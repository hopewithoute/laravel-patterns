<?php

namespace Labtime\AiRuntime\Execution\Hooks;

use Labtime\AiRuntime\Execution\RuntimeRunReport;
use Labtime\AiRuntime\Foundation\Contracts\PostRunHook;

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
