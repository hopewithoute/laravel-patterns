<?php

namespace Labtime\AiRuntime\Artifacts\Registry;

use Labtime\AiRuntime\Artifacts\ArtifactPayload;

readonly class ArtifactSchemaValidator
{
    public function __construct(
        private ArtifactRegistry $artifactRegistry,
    ) {}

    public function isRegistered(string $artifactType): bool
    {
        return $this->artifactRegistry->find($artifactType) !== null;
    }

    public function validatePayload(ArtifactPayload $artifact): bool
    {
        return $this->validate($artifact->type, $artifact->data);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validateExplicitPayload(array $payload): bool
    {
        $artifactType = $payload['artifactType'] ?? null;
        $data = $payload['data'] ?? null;

        return is_string($artifactType)
            && is_array($data)
            && $this->validate($artifactType, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(string $artifactType, array $data): bool
    {
        $definition = $this->artifactRegistry->find($artifactType);

        if ($definition === null) {
            return false;
        }

        return $definition->validateData($data);
    }
}
