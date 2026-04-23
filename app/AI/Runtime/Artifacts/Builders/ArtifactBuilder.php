<?php

namespace App\AI\Runtime\Artifacts\Builders;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Tools\ToolExecutionResult;

interface ArtifactBuilder
{
    public function supports(string $toolName): bool;

    public function build(ToolExecutionResult $result, ArtifactIntent $intent): ?ArtifactPayload;
}
