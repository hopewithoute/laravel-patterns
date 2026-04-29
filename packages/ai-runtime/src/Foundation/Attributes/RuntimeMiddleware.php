<?php

namespace Labtime\AiRuntime\Foundation\Attributes;

use Attribute;
use Labtime\AiRuntime\Foundation\Enums\RuntimeMiddlewareStage;

#[Attribute(Attribute::TARGET_CLASS)]
class RuntimeMiddleware
{
    public function __construct(
        public int $priority = 0,
        public string $stage = RuntimeMiddlewareStage::Prompt->value,
    ) {}
}
