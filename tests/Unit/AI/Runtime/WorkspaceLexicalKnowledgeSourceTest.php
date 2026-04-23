<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Retrieval\RetrievalPlan;
use App\AI\Runtime\Retrieval\SqliteFtsLexicalSearchIndex;
use App\AI\Runtime\Retrieval\WorkspaceLexicalKnowledgeSource;
use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceLexicalKnowledgeSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_retrieves_workspace_knowledge_matches_from_the_lexical_index(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $source = AiKnowledgeSource::query()->create([
            'organization_id' => $organization->id,
            'project_id' => null,
            'user_id' => $user->id,
            'source_type' => 'workspace_note',
            'title' => 'Release runbook',
            'content' => 'Checklist for release day.',
            'reference_uri' => '/docs/release-runbook',
        ]);

        $chunk = AiKnowledgeChunk::query()->create([
            'knowledge_source_id' => $source->id,
            'organization_id' => $organization->id,
            'project_id' => null,
            'chunk_index' => 0,
            'content' => 'Validate the release checklist and rollback plan before deployment.',
            'token_count' => 9,
            'content_hash' => hash('sha256', 'Validate the release checklist and rollback plan before deployment.'),
            'meta' => [
                'source_type' => 'workspace_note',
                'title' => 'Release runbook',
            ],
        ]);

        $index = new SqliteFtsLexicalSearchIndex;
        $index->indexChunks([[
            'chunk_id' => $chunk->id,
            'knowledge_source_id' => $source->id,
            'organization_id' => $organization->id,
            'project_id' => null,
            'title' => $source->title,
            'content' => $chunk->content,
        ]]);

        $knowledgeSource = new WorkspaceLexicalKnowledgeSource(
            $index,
            new AiKnowledgeChunk,
        );

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Summarize the release runbook.',
        );

        $result = $knowledgeSource->retrieve($context, new RetrievalPlan(
            required: true,
            query: $context->prompt,
            sources: ['lexical_docs'],
            filters: ['lexical_limit' => 3],
        ));

        $this->assertCount(1, $result->documents);
        $this->assertSame('knowledge_chunk', $result->documents[0]['type']);
        $this->assertSame('Release runbook', $result->documents[0]['title']);
        $this->assertStringContainsString('Relevant lexical matches:', $result->metadata['summary']);
        $this->assertSame(1, $result->metadata['documents_count']);
        $this->assertSame('sqlite_fts5', $result->metadata['driver']);
    }
}
