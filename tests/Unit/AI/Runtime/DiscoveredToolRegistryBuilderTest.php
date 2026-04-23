<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Tools\Registry\DiscoveredToolRegistryBuilder;
use App\AI\Tools\CreateTaskTool;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Tests\TestCase;

class DiscoveredToolRegistryBuilderTest extends TestCase
{
    public function test_it_builds_tool_definitions_from_runtime_tool_attributes(): void
    {
        $definitions = app(DiscoveredToolRegistryBuilder::class)->build();

        $this->assertSame([
            'CreateTaskTool',
            'LookupProjectsTool',
            'LookupWorkspaceUsersTool',
        ], array_map(
            fn ($definition): string => $definition->name,
            $definitions,
        ));

        $createTaskDefinition = collect($definitions)->firstWhere('name', 'CreateTaskTool');

        $this->assertNotNull($createTaskDefinition);
        $this->assertSame('create_task', $createTaskDefinition->uiIdentifier);
        $this->assertSame(['title'], $createTaskDefinition->requiredInputs);
        $this->assertSame('task.create', $createTaskDefinition->capability);
        $this->assertSame('write', $createTaskDefinition->operation);
        $this->assertSame('workspace', $createTaskDefinition->scope);
        $this->assertSame(CreateTaskTool::class, $createTaskDefinition->toolClass);
        $this->assertStringContainsString('Use when:', $createTaskDefinition->llmDescription());
        $this->assertArrayHasKey('title', $createTaskDefinition->schema(new JsonSchemaTypeFactory));
    }
}
