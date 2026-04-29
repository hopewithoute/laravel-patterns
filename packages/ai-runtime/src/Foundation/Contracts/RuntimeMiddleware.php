<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Closure;
use Labtime\AiRuntime\Foundation\State\RuntimeState;

interface RuntimeMiddleware
{
    /**
     * @param  Closure(RuntimeState): RuntimeState  $next
     */
    public function handle(RuntimeState $state, Closure $next): RuntimeState;
}
