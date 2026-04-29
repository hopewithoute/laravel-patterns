<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Labtime\AiRuntime\Foundation\Context\RuntimeTenant;
use Laravel\Ai\Contracts\Tool;

interface TenantScopedTool extends Tool
{
    public function forTenant(RuntimeTenant|string $tenant): self;
}
