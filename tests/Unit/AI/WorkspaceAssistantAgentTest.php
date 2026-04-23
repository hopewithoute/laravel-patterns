<?php

namespace Tests\Unit\AI;

use App\AI\Agents\WorkspaceAssistantAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceAssistantAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_injected_instructions(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $agent = new WorkspaceAssistantAgent(
            user: $user,
            organization: $organization,
            instructions: 'Prepared runtime instructions.',
            tools: [],
        );

        $this->assertSame('Prepared runtime instructions.', (string) $agent->instructions());
    }

    public function test_it_returns_the_injected_tools(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $tools = ['fake-tool', 'other-tool'];

        $agent = new WorkspaceAssistantAgent(
            user: $user,
            organization: $organization,
            instructions: 'Prepared runtime instructions.',
            tools: $tools,
        );

        $this->assertSame($tools, iterator_to_array($agent->tools()));
    }
}
