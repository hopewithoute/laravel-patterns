<?php

namespace App\AI\Runtime\Contracts;

use App\Models\Organization;
use Laravel\Ai\Contracts\Tool;

interface WorkspaceScopedTool extends Tool
{
    /**
     * Resolve the tool specifically for the given organization context.
     */
    public function forWorkspace(Organization|string $organization): self;
}
