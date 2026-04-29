<?php

namespace Labtime\AiRuntime\Execution\Hooks\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RuntimeHook
{
    public function __construct(
        public int $priority = 0,
    ) {}
}
