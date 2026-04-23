<?php

namespace App\AI\Runtime\Middleware;

use App\AI\Runtime\Artifacts\Registry\ArtifactCatalogBuilder;
use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\PromptMiddleware;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Middleware\Attributes\RuntimeMiddleware;
use Closure;

#[RuntimeMiddleware(priority: 40)]
readonly class WorkspaceArtifactPromptMiddleware implements PromptMiddleware
{
    public function __construct(
        private ArtifactCatalogBuilder $artifactCatalogBuilder,
    ) {}

    /**
     * @param  Closure(string): string  $next
     */
    public function handle(AiRuntimeContext $context, string $instructions, Closure $next): string
    {
        $artifactInstruction = $context->requestedArtifactMode === ArtifactIntent::Auto
            ? 'Keep the reply concise and operational.'
            : "When a tool succeeds, align your response with the requested output mode: {$context->requestedArtifactMode->value}.";

        return $next(trim(implode(' ', array_filter([
            $instructions,
            $artifactInstruction,
            $this->artifactCatalogBuilder->build(),
        ]))));
    }
}
