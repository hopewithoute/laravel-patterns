<?php

namespace App\AI\Agents;

use App\Models\Organization;
use App\Models\User;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxSteps(3)]
class WorkspaceAssistantAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    /**
     * @param  array<int, mixed>  $tools
     */
    public function __construct(
        public User $user,
        public Organization $organization,
        public string $instructions,
        public array $tools = [],
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return $this->instructions;
    }

    public function tools(): iterable
    {
        return $this->tools;
    }
}
