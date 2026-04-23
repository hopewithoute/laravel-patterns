<?php

namespace App\AI\Runtime\Retrieval;

use App\AI\Runtime\Contracts\LexicalSearchIndex;

class NullLexicalSearchIndex implements LexicalSearchIndex
{
    public function indexChunks(array $rows): void {}

    public function deleteByKnowledgeSourceIds(array $knowledgeSourceIds): void {}

    public function search(string $query, string $organizationId, ?string $projectId = null, int $limit = 5): array
    {
        return [];
    }

    public function driverName(): string
    {
        return 'null';
    }
}
