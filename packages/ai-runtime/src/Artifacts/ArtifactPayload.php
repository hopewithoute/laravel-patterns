<?php

namespace Labtime\AiRuntime\Artifacts;

use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;

readonly class ArtifactPayload
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public ArtifactIntent $intent,
        public string $type,
        public string $id,
        public string $title,
        public int $version = 1,
        public array $data = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'artifact',
            'artifactType' => $this->type,
            'intent' => $this->intent->value,
            'id' => $this->id,
            'title' => $this->title,
            'version' => $this->version,
            'data' => $this->data,
            'meta' => $this->meta,
        ];
    }
}
