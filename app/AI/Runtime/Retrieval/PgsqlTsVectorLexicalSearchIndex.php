<?php

namespace App\AI\Runtime\Retrieval;

use App\AI\Runtime\Contracts\LexicalSearchIndex;
use Illuminate\Support\Facades\DB;

readonly class PgsqlTsVectorLexicalSearchIndex implements LexicalSearchIndex
{
    public function __construct(
        private string $language = 'simple',
    ) {}

    public function indexChunks(array $rows): void
    {
        //
    }

    public function deleteByKnowledgeSourceIds(array $knowledgeSourceIds): void
    {
        //
    }

    public function search(string $query, string $organizationId, ?string $projectId = null, int $limit = 5): array
    {
        $normalizedQuery = trim($query);

        if ($normalizedQuery === '') {
            return [];
        }

        $bindings = [
            $this->language,
            $this->language,
            $normalizedQuery,
            $organizationId,
            $this->language,
            $this->language,
            $normalizedQuery,
        ];
        $projectCondition = '';

        if ($projectId !== null) {
            $projectCondition = ' AND (project_id = ? OR project_id IS NULL)';
            $bindings[] = $projectId;
        }

        $bindings[] = max(1, $limit);

        $rows = DB::select(
            "SELECT id AS chunk_id,
                ts_rank_cd(
                    to_tsvector(?::regconfig, {$this->documentExpression()}),
                    plainto_tsquery(?::regconfig, ?)
                ) AS score
            FROM ai_knowledge_chunks
            WHERE organization_id = ?
              AND to_tsvector(?::regconfig, {$this->documentExpression()}) @@ plainto_tsquery(?::regconfig, ?)
              {$projectCondition}
            ORDER BY score DESC, chunk_index ASC
            LIMIT ?",
            $bindings,
        );

        return collect($rows)
            ->map(fn (object $row): array => [
                'chunk_id' => (string) $row->chunk_id,
                'score' => (float) $row->score,
            ])
            ->all();
    }

    public function driverName(): string
    {
        return 'pgsql_tsvector';
    }

    private function documentExpression(): string
    {
        return "concat_ws(' ', coalesce(content, ''), coalesce(meta->>'title', ''))";
    }
}
