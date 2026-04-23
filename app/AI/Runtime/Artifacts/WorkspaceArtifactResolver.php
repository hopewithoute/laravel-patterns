<?php

namespace App\AI\Runtime\Artifacts;

use App\AI\Runtime\Artifacts\Builders\ArtifactBuilder;
use App\AI\Runtime\Artifacts\Registry\ArtifactRegistry;
use App\AI\Runtime\Artifacts\Registry\ArtifactSchemaValidator;
use App\AI\Runtime\Artifacts\Registry\ArtifactTypeDefinition;
use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\ArtifactResolver;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalResult;
use App\AI\Runtime\Tools\ToolExecutionResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WorkspaceArtifactResolver implements ArtifactResolver
{
    use DecodesToolResults;

    /**
     * @param  iterable<ArtifactBuilder>  $builders
     */
    public function __construct(
        private iterable $builders = [],
        private ?ArtifactRegistry $artifactRegistry = null,
        private ?ArtifactSchemaValidator $artifactSchemaValidator = null,
    ) {}

    /**
     * @param  array<int, ToolExecutionResult>  $toolResults
     * @return array<int, ArtifactPayload>
     */
    public function resolve(
        AiRuntimeContext $context,
        PreflightDecision $decision,
        array $toolResults = [],
        ?RetrievalResult $retrievalResult = null,
        ?string $assistantText = null,
    ): array {
        return collect($toolResults)
            ->map(fn (ToolExecutionResult $toolResult): ArtifactPayload => $this->buildArtifact($context, $decision, $toolResult))
            ->values()
            ->all();
    }

    private function buildArtifact(
        AiRuntimeContext $context,
        PreflightDecision $decision,
        ToolExecutionResult $toolResult,
    ): ArtifactPayload {
        if (! $toolResult->successful) {
            return $this->validatedArtifact(
                $this->buildToolFailureArtifact($toolResult),
                $toolId = $this->resolveToolId($toolResult),
                $toolResult->toolName,
                $toolResult->result,
                $resolvedIntent = ArtifactIntent::None,
            );
        }

        $toolId = $this->resolveToolId($toolResult);
        $decodedResult = $this->decodeResult($toolResult->result);
        $resolvedIntent = $decision->artifactIntent;

        $explicitArtifact = $this->extractExplicitArtifact(
            toolId: $toolId,
            toolName: $toolResult->toolName,
            decodedResult: $decodedResult,
            defaultIntent: ArtifactIntent::Auto,
        );

        if ($explicitArtifact !== null) {
            return $this->validatedArtifact($explicitArtifact, $toolId, $toolResult->toolName, $decodedResult, $resolvedIntent);
        }

        foreach ($this->builders as $builder) {
            if ($builder->supports($toolResult->toolName)) {
                $presented = $builder->build($toolResult, $resolvedIntent);

                if ($presented !== null) {
                    return $this->validatedArtifact($presented, $toolId, $toolResult->toolName, $decodedResult, $resolvedIntent);
                }
            }
        }

        return $this->buildJsonFallbackArtifact($toolId, $toolResult->toolName, $decodedResult, $resolvedIntent);
    }

    private function extractExplicitArtifact(
        string $toolId,
        string $toolName,
        mixed $decodedResult,
        ArtifactIntent $defaultIntent,
    ): ?ArtifactPayload {
        if (! is_array($decodedResult)
            || ($decodedResult['type'] ?? null) !== 'artifact'
            || ! $this->artifactSchemaValidator?->validateExplicitPayload($decodedResult)) {
            return null;
        }

        $artifactType = $decodedResult['artifactType'] ?? null;
        $data = $decodedResult['data'] ?? null;

        if (! is_string($artifactType) || $artifactType === '' || ! is_array($data)) {
            return null;
        }

        $definition = $this->artifactRegistry?->find($artifactType);

        return new ArtifactPayload(
            intent: $definition?->defaultIntent ?? $defaultIntent,
            type: $artifactType,
            id: (string) ($decodedResult['id'] ?? $toolId),
            title: (string) ($decodedResult['title'] ?? $definition?->label ?? Str::headline($toolName)),
            version: (int) ($decodedResult['version'] ?? 1),
            data: $data,
            meta: [
                'tool_id' => $toolId,
                'tool_name' => $toolName,
                'source' => 'explicit_tool_artifact',
            ],
        );
    }

    private function buildJsonFallbackArtifact(
        string $toolId,
        string $toolName,
        mixed $decodedResult,
        ArtifactIntent $defaultIntent,
    ): ArtifactPayload {
        $definition = $this->artifactRegistry?->find('json_fallback');

        return new ArtifactPayload(
            intent: $definition?->defaultIntent ?? $defaultIntent,
            type: 'json_fallback',
            id: $toolId,
            title: $definition?->label ?? Str::headline($toolName),
            data: [
                'tool_name' => $toolName,
                'result' => $decodedResult,
            ],
            meta: [
                'tool_id' => $toolId,
                'tool_name' => $toolName,
                'source' => 'runtime_artifact_resolver',
                'renderer' => $definition?->renderer,
            ],
        );
    }

    private function buildToolFailureArtifact(ToolExecutionResult $toolResult): ArtifactPayload
    {
        $toolId = $this->resolveToolId($toolResult);
        $definition = $this->artifactRegistry?->find('key_value');
        $failureType = $toolResult->failureType ?? (string) Arr::get($toolResult->metadata, 'failure_type', 'unknown_error');
        $failureBehavior = $toolResult->failureBehavior !== 'none'
            ? $toolResult->failureBehavior
            : (string) Arr::get($toolResult->metadata, 'failure_behavior', 'surface_to_user');

        return new ArtifactPayload(
            intent: $definition?->defaultIntent ?? ArtifactIntent::None,
            type: 'key_value',
            id: $toolId,
            title: 'Action blocked',
            data: [
                'tool' => $toolResult->toolName,
                'status' => 'failed',
                'failure_type' => $failureType,
                'failure_behavior' => $failureBehavior,
                'message' => $toolResult->error,
            ],
            meta: [
                'tool_id' => $toolId,
                'tool_name' => $toolResult->toolName,
                'source' => 'runtime_tool_failure_resolver',
                'renderer' => $definition?->renderer,
            ],
        );
    }

    private function validatedArtifact(
        ArtifactPayload $artifact,
        string $toolId,
        string $toolName,
        mixed $decodedResult,
        ArtifactIntent $intent,
    ): ArtifactPayload {
        if ($this->artifactSchemaValidator?->validatePayload($artifact) ?? true) {
            return $this->normalizeArtifactFromRegistry($artifact);
        }

        return $this->buildJsonFallbackArtifact($toolId, $toolName, $decodedResult, $intent);
    }

    private function normalizeArtifactFromRegistry(ArtifactPayload $artifact): ArtifactPayload
    {
        $definition = $this->artifactRegistry?->find($artifact->type);

        if (! $definition instanceof ArtifactTypeDefinition) {
            return $artifact;
        }

        return new ArtifactPayload(
            intent: $definition->defaultIntent !== ArtifactIntent::Auto ? $definition->defaultIntent : $artifact->intent,
            type: $definition->type,
            id: $artifact->id,
            title: $artifact->title !== '' ? $artifact->title : $definition->label,
            version: $artifact->version,
            data: $artifact->data,
            meta: [
                ...$artifact->meta,
                'renderer' => $definition->renderer,
            ],
        );
    }
}
