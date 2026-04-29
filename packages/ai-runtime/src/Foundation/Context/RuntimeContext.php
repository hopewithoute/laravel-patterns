<?php

namespace Labtime\AiRuntime\Foundation\Context;

use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;

readonly class RuntimeContext
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $runtimeId,
        public RuntimeActor $actor,
        public RuntimeTenant $tenant,
        public ?RuntimeSession $runtimeSession,
        public string $prompt,
        public ArtifactIntent $requestedArtifactMode = ArtifactIntent::Auto,
        public bool $debug = false,
        public ?string $provider = null,
        public ?string $model = null,
        public array $capabilities = [],
        public array $attributes = [],
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            runtimeId: $this->runtimeId,
            actor: $this->actor,
            tenant: $this->tenant,
            runtimeSession: $this->runtimeSession,
            prompt: $this->prompt,
            requestedArtifactMode: $this->requestedArtifactMode,
            debug: $this->debug,
            provider: $this->provider,
            model: $this->model,
            capabilities: $this->capabilities,
            attributes: $this->attributes,
            metadata: [...$this->metadata, ...$metadata],
        );
    }

    public function requestId(): ?string
    {
        $requestId = $this->metadata['request_id'] ?? null;

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
