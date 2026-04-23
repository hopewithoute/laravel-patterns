<?php

namespace App\AI\Runtime\Retrieval;

use App\AI\Runtime\Contracts\LexicalSearchIndex;
use Illuminate\Support\Facades\DB;

class SqliteFtsLexicalSearchIndex implements LexicalSearchIndex
{
    public function indexChunks(array $rows): void
    {
        foreach ($rows as $row) {
            DB::table('ai_knowledge_chunk_fts')->insert([
                'chunk_id' => (string) ($row['chunk_id'] ?? ''),
                'knowledge_source_id' => (string) ($row['knowledge_source_id'] ?? ''),
                'organization_id' => (string) ($row['organization_id'] ?? ''),
                'project_id' => $row['project_id'] ?? null,
                'title' => (string) ($row['title'] ?? ''),
                'content' => (string) ($row['content'] ?? ''),
            ]);
        }
    }

    public function deleteByKnowledgeSourceIds(array $knowledgeSourceIds): void
    {
        $normalizedIds = array_values(array_filter(
            $knowledgeSourceIds,
            fn (mixed $id): bool => is_string($id) && $id !== '',
        ));

        if ($normalizedIds === []) {
            return;
        }

        DB::table('ai_knowledge_chunk_fts')
            ->whereIn('knowledge_source_id', $normalizedIds)
            ->delete();
    }

    public function search(string $query, string $organizationId, ?string $projectId = null, int $limit = 5): array
    {
        $matchQuery = $this->matchQueryFor($query);

        if ($matchQuery === '') {
            return [];
        }

        $bindings = [$matchQuery, $organizationId];
        $projectCondition = '';

        if ($projectId !== null) {
            $projectCondition = ' AND (project_id = ? OR project_id IS NULL)';
            $bindings[] = $projectId;
        }

        $bindings[] = max(1, $limit);

        $rows = DB::select(
            "SELECT chunk_id, bm25(ai_knowledge_chunk_fts) * -1 as score
            FROM ai_knowledge_chunk_fts
            WHERE ai_knowledge_chunk_fts MATCH ?
              AND organization_id = ?{$projectCondition}
            ORDER BY bm25(ai_knowledge_chunk_fts) ASC
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
        return 'sqlite_fts5';
    }

    private function matchQueryFor(string $query): string
    {
        $terms = collect(preg_split('/\s+/', mb_strtolower($query)) ?: [])
            ->map(fn (string $term): string => trim($term, " \t\n\r\0\x0B,.:;!?()[]{}\"'`"))
            ->filter(fn (string $term): bool => mb_strlen($term) >= 2)
            ->unique()
            ->take(8)
            ->values();

        if ($terms->isEmpty()) {
            return '';
        }

        return $terms
            ->map(fn (string $term): string => '"'.$term.'"')
            ->implode(' OR ');
    }
}
