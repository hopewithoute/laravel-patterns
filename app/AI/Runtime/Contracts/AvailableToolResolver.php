<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use Laravel\Ai\Contracts\Tool;

interface AvailableToolResolver
{
    /**
     * @return array<int, Tool>
     */
    public function resolve(AiRuntimeContext $context): array;

    /**
     * @return array<int, string>
     */
    public function uiIdentifiers(): array;

    public function promptInstruction(): string;
}
