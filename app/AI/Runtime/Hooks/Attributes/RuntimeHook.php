<?php

namespace App\AI\Runtime\Hooks\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class RuntimeHook
{
    public function __construct(public int $priority = 50) {}
}
