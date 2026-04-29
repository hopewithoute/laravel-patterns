<?php

namespace Labtime\AiRuntime\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Labtime\AiRuntime\Tools\Attributes\RuntimeTool;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[RuntimeTool(
    name: 'dummy-tool',
    uiIdentifier: 'dummy_tool',
    label: 'Dummy Tool',
    description: 'A package-local tool fixture.',
    whenToUse: 'You need a deterministic tool for testing.',
    whenNotToUse: 'You are executing real workspace actions.',
    requiredInputs: ['query'],
    outputContract: 'Returns a dummy string payload.',
)]
class DummyRuntimeTool implements Tool
{
    public function description(): string
    {
        return 'Dummy runtime tool';
    }

    public function handle(Request $request): string
    {
        return 'handled';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema::string(),
        ];
    }
}
