<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Retrieval\RetrievalPlan;
use App\AI\Runtime\Retrieval\WorkspaceDatabaseKnowledgeSource;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceDatabaseKnowledgeSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_retrieves_workspace_scoped_projects_and_tasks_only(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        [, $otherOrganization] = $this->createWorkspaceUser(['email' => 'other@example.com']);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Mobile Redesign',
            'description' => 'Core mobile app redesign initiative.',
        ]);

        $otherProject = Project::factory()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Hidden Project',
        ]);

        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Fix onboarding flow',
            'description' => 'Review the onboarding backlog for mobile.',
            'status' => TaskStatus::InProgress,
            'priority' => Priority::High,
        ]);

        Task::factory()->create([
            'organization_id' => $otherOrganization->id,
            'project_id' => $otherProject->id,
            'title' => 'Should never leak',
        ]);

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Show mobile redesign onboarding tasks.',
        );

        $result = app(WorkspaceDatabaseKnowledgeSource::class)->retrieve($context, new RetrievalPlan(
            required: true,
            query: $context->prompt,
            sources: ['workspace_db'],
            filters: [
                'project_limit' => 3,
                'task_limit' => 5,
            ],
        ));

        $this->assertCount(2, $result->documents);
        $this->assertStringContainsString('Mobile Redesign', $result->metadata['summary']);
        $this->assertStringContainsString('Fix onboarding flow', $result->metadata['summary']);
        $this->assertStringNotContainsString('Hidden Project', $result->metadata['summary']);
        $this->assertSame($project->id, $result->documents[0]['id']);
        $this->assertSame($task->id, $result->documents[1]['id']);
        $this->assertSame(['workspace_db'], $result->metadata['sources']);
        $this->assertSame(2, $result->metadata['documents_count']);
        $this->assertSame('database', $result->metadata['driver']);
    }
}
