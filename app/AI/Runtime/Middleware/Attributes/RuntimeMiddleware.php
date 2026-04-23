<?php

namespace App\AI\Runtime\Middleware\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class RuntimeMiddleware
{
    public function __construct(public int $priority = 50) {}
}
