<?php

namespace Labtime\AiRuntime\Foundation\Context;

readonly class RuntimeActor
{
    /**
     * @param  array<int, string>  $capabilities
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $role = 'member',
        public array $capabilities = [],
        public array $attributes = [],
    ) {}
}
