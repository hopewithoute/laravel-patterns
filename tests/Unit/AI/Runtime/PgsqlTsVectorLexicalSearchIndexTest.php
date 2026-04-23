<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Retrieval\PgsqlTsVectorLexicalSearchIndex;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PgsqlTsVectorLexicalSearchIndexTest extends TestCase
{
    public function test_it_builds_a_ranked_postgresql_full_text_query(): void
    {
        $index = new PgsqlTsVectorLexicalSearchIndex('simple');

        DB::shouldReceive('select')
            ->once()
            ->withArgs(function (string $sql, array $bindings): bool {
                $this->assertStringContainsString('ts_rank_cd', $sql);
                $this->assertStringContainsString("meta->>'title'", $sql);
                $this->assertStringContainsString('ORDER BY score DESC, chunk_index ASC', $sql);
                $this->assertStringContainsString('AND (project_id = ? OR project_id IS NULL)', $sql);
                $this->assertSame([
                    'simple',
                    'simple',
                    'release rollback',
                    'org-001',
                    'simple',
                    'simple',
                    'release rollback',
                    'project-001',
                    5,
                ], $bindings);

                return true;
            })
            ->andReturn([
                (object) [
                    'chunk_id' => 'chunk-001',
                    'score' => 0.87,
                ],
            ]);

        $matches = $index->search('release rollback', 'org-001', 'project-001', 5);

        $this->assertSame('pgsql_tsvector', $index->driverName());
        $this->assertSame([
            [
                'chunk_id' => 'chunk-001',
                'score' => 0.87,
            ],
        ], $matches);
    }

    public function test_it_returns_no_matches_for_blank_queries(): void
    {
        $index = new PgsqlTsVectorLexicalSearchIndex;

        DB::shouldReceive('select')->never();

        $this->assertSame([], $index->search('   ', 'org-001'));
    }

    public function test_indexing_and_delete_calls_are_no_ops_for_direct_table_search(): void
    {
        $index = new PgsqlTsVectorLexicalSearchIndex;

        $index->indexChunks([
            [
                'chunk_id' => 'chunk-001',
            ],
        ]);
        $index->deleteByKnowledgeSourceIds(['source-001']);

        $this->assertSame('pgsql_tsvector', $index->driverName());
    }
}
