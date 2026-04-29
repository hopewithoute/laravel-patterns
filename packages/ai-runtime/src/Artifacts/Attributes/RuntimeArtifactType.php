<?php

namespace Labtime\AiRuntime\Artifacts\Attributes;

use Attribute;
use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class RuntimeArtifactType
{
    public function __construct(
        public string $type,
        public string $label,
        public string $description,
        public ?string $renderer = null,
        public ?string $llmUsageGuidance = null,
        public array $requiredDataKeys = [],
        public string $presentationContract = '',
        public ArtifactIntent $defaultIntent = ArtifactIntent::Auto,
        public bool $enabled = true,
        public string $version = '1.0.0',
        public ?string $fallbackType = null,
    ) {}
}
