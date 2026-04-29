<?php

namespace Labtime\AiRuntime\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Labtime\AiRuntime\Tools\Attributes\RuntimeTool;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[RuntimeTool(
    name: 'disabled-tool',
    uiIdentifier: 'disabled_tool',
    label: 'Disabled Tool',
    description: 'A disabled package-local tool fixture.',
    whenToUse: 'Never.',
    whenNotToUse: 'Always.',
    enabled: false,
)]
class DisabledRuntimeTool implements Tool
{
    public function description(): string
    {
        return 'Disabled runtime tool';
    }

    public function handle(Request $request): string
    {
        return 'disabled';
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
