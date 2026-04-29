<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Labtime\AiRuntime\RuntimeBuilder;

interface RuntimeDefinition
{
    public function define(RuntimeBuilder $runtime): void;
}
