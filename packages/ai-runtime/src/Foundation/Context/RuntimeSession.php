<?php

namespace Labtime\AiRuntime\Foundation\Context;

readonly class RuntimeSession
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $id,
        public ?string $conversationId = null,
    ) {}
}
