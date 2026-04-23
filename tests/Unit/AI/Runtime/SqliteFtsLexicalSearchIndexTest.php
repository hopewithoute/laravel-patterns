<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Retrieval\SqliteFtsLexicalSearchIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SqliteFtsLexicalSearchIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_indexes_and_searches_knowledge_chunks_with_sqlite_fts5(): void
    {
        $index = new SqliteFtsLexicalSearchIndex;

        $index->indexChunks([
            [
                'chunk_id' => 'chunk-001',
                'knowledge_source_id' => 'source-001',
                'organization_id' => 'org-001',
                'project_id' => 'project-001',
                'title' => 'Release runbook',
                'content' => 'Checklist for release validation and rollback planning.',
            ],
            [
                'chunk_id' => 'chunk-002',
                'knowledge_source_id' => 'source-002',
                'organization_id' => 'org-001',
                'project_id' => null,
                'title' => 'Incident guide',
                'content' => 'Escalation contacts for incident response.',
            ],
        ]);

        $matches = $index->search('release rollback', 'org-001', 'project-001', 5);

        $this->assertSame('sqlite_fts5', $index->driverName());
        $this->assertCount(1, $matches);
        $this->assertSame('chunk-001', $matches[0]['chunk_id']);

        $index->deleteByKnowledgeSourceIds(['source-001']);

        $this->assertSame([], $index->search('release rollback', 'org-001', 'project-001', 5));
    }
}
