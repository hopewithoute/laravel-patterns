<?php

namespace Labtime\AiRuntime\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Labtime\AiRuntime\Artifacts\Attributes\RuntimeArtifactType;

#[RuntimeArtifactType(
    type: 'dummy_card',
    label: 'Dummy Card',
    description: 'A package-local artifact fixture.',
    renderer: 'dummy-card',
    llmUsageGuidance: 'Use for package tests.',
    requiredDataKeys: ['title'],
    presentationContract: 'Provide a title field.',
)]
#[RuntimeArtifactType(
    type: 'disabled_card',
    label: 'Disabled Card',
    description: 'A disabled package-local artifact fixture.',
    renderer: 'disabled-card',
    llmUsageGuidance: 'Never use.',
    enabled: false,
)]
class DummyArtifactDefinition
{
    public function validate(string $type, array $data): bool
    {
        return match ($type) {
            'dummy_card' => is_string($data['title'] ?? null),
            default => false,
        };
    }

    public function schema(string $type, JsonSchema $schema): array
    {
        return [];
    }
}
