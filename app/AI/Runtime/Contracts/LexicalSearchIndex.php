<?php

namespace App\AI\Runtime\Contracts;

interface LexicalSearchIndex
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function indexChunks(array $rows): void;

    /**
     * @param  array<int, string>  $knowledgeSourceIds
     */
    public function deleteByKnowledgeSourceIds(array $knowledgeSourceIds): void;

    /**
     * @return array<int, array{chunk_id: string, score: float}>
     */
    public function search(string $query, string $organizationId, ?string $projectId = null, int $limit = 5): array;

    public function driverName(): string;
}
