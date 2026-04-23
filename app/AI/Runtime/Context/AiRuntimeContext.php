<?php

namespace App\AI\Runtime\Context;

use App\AI\Runtime\Enums\ArtifactIntent;
use App\Models\AiChatSession;
use App\Models\Organization;
use App\Models\User;

readonly class AiRuntimeContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public User $user,
        public Organization $organization,
        public ?AiChatSession $session,
        public string $prompt,
        public ArtifactIntent $requestedArtifactMode = ArtifactIntent::Auto,
        public bool $debug = false,
        public ?string $provider = null,
        public ?string $model = null,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function make(
        User $user,
        Organization $organization,
        ?AiChatSession $session,
        string $prompt,
        ArtifactIntent $requestedArtifactMode = ArtifactIntent::Auto,
        bool $debug = false,
        ?string $provider = null,
        ?string $model = null,
        array $metadata = [],
    ): self {
        return new self(
            user: $user,
            organization: $organization,
            session: $session,
            prompt: $prompt,
            requestedArtifactMode: $requestedArtifactMode,
            debug: $debug,
            provider: $provider,
            model: $model,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            ...[
                ...get_object_vars($this),
                'metadata' => [...$this->metadata, ...$metadata],
            ],
        );
    }
}
