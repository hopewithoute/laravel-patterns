<?php

namespace Labtime\AiRuntime\Foundation\Context;

readonly class RuntimeTenant
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $attributes = [],
    ) {}
}
