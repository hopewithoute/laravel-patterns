<?php

namespace Labtime\AiRuntime\Observability\Recording;

use Labtime\AiRuntime\Foundation\Context\RuntimeContext;

readonly class RunStartData
{
    public function __construct(
        public string $runId,
        public ?string $conversationId = null,
        public ?string $sessionId = null,
        public ?string $tenantId = null,
        public ?string $actorId = null,
        public ?string $requestId = null,
        public ?string $provider = null,
        public ?string $model = null,
        public string $prompt = '',
        public ?string $artifactIntent = null,
        public bool $debug = false,
    ) {}

    public static function fromContext(RuntimeContext $context): self
    {
        return new self(
            runId: $context->runtimeId,
            conversationId: $context->runtimeSession?->conversationId,
            sessionId: $context->runtimeSession?->id,
            tenantId: $context->tenant->id,
            actorId: $context->actor->id,
            requestId: $context->requestId(),
            provider: $context->provider,
            model: $context->model,
            prompt: $context->prompt,
            artifactIntent: $context->requestedArtifactMode->value,
            debug: $context->debug,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'prompt' => $this->prompt,
            'artifact_intent' => $this->artifactIntent,
            'debug' => $this->debug,
        ];
    }
}
