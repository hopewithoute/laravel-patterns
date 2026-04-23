<?php

namespace App\AI\Runtime\Retrieval;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\KnowledgeSource;
use App\AI\Runtime\Contracts\LexicalSearchIndex;
use App\Models\AiKnowledgeChunk;
use Illuminate\Support\Str;

readonly class WorkspaceLexicalKnowledgeSource implements KnowledgeSource
{
    public function __construct(
        private LexicalSearchIndex $lexicalSearchIndex,
        private AiKnowledgeChunk $chunkModel,
    ) {}

    public function supports(RetrievalPlan $plan): bool
    {
        return $plan->required
            && in_array('lexical_docs', $plan->sources, true)
            && $this->lexicalSearchIndex->driverName() !== 'null';
    }

    public function retrieve(AiRuntimeContext $context, RetrievalPlan $plan): RetrievalResult
    {
        if (! $this->supports($plan)) {
            return RetrievalResult::empty();
        }

        $matches = $this->lexicalSearchIndex->search(
            query: $plan->query ?? $context->prompt,
            organizationId: $context->organization->id,
            projectId: $context->metadata['project_id'] ?? null,
            limit: (int) ($plan->filters['lexical_limit'] ?? 5),
        );

        if ($matches === []) {
            return new RetrievalResult(
                query: $plan->query ?? $context->prompt,
                metadata: [
                    'summary' => null,
                    'sources' => ['lexical_docs'],
                    'match_count' => 0,
                ],
            );
        }

        $chunks = $this->chunkModel->newQuery()
            ->with('source:id,title,reference_uri,source_type')
            ->whereIn('id', array_column($matches, 'chunk_id'))
            ->get()
            ->keyBy('id');
        $ordered = collect($matches)
            ->map(function (array $match) use ($chunks): ?array {
                $chunk = $chunks->get($match['chunk_id']);

                if ($chunk === null) {
                    return null;
                }

                return [
                    'chunk' => $chunk,
                    'score' => (float) $match['score'],
                ];
            })
            ->filter()
            ->values();
        $documents = $this->mapDocuments($ordered->all());

        return new RetrievalResult(
            query: $plan->query ?? $context->prompt,
            documents: $documents,
            metadata: [
                'summary' => $this->buildSummary($ordered->all()),
                'sources' => ['lexical_docs'],
                'documents_count' => count($documents),
                'match_count' => $ordered->count(),
                'driver' => $this->lexicalSearchIndex->driverName(),
            ],
        );
    }

    /**
     * @param  array<int, array{chunk: AiKnowledgeChunk, score: float}>  $matches
     * @return array<int, array<string, mixed>>
     */
    private function mapDocuments(array $matches): array
    {
        return collect($matches)
            ->map(fn (array $match): array => [
                'type' => 'knowledge_chunk',
                'id' => $match['chunk']->id,
                'title' => $match['chunk']->source?->title ?? ($match['chunk']->meta['title'] ?? 'Workspace knowledge'),
                'content' => $match['chunk']->content,
                'metadata' => [
                    'chunk_index' => $match['chunk']->chunk_index,
                    'knowledge_source_id' => $match['chunk']->knowledge_source_id,
                    'organization_id' => $match['chunk']->organization_id,
                    'project_id' => $match['chunk']->project_id,
                    'reference_uri' => $match['chunk']->source?->reference_uri,
                    'score' => $match['score'],
                    'source_type' => 'lexical_docs',
                ],
            ])
            ->all();
    }

    /**
     * @param  array<int, array{chunk: AiKnowledgeChunk, score: float}>  $matches
     */
    private function buildSummary(array $matches): ?string
    {
        $summary = collect($matches)
            ->map(function (array $match): string {
                $title = $match['chunk']->source?->title ?? ($match['chunk']->meta['title'] ?? 'Workspace knowledge');
                $sourceId = $match['chunk']->knowledge_source_id;
                $excerpt = Str::limit($match['chunk']->content, 140);

                return "- Lexical match {$title} [{$sourceId}] score=".number_format($match['score'], 3).' excerpt='.$excerpt;
            })
            ->implode("\n");

        return $summary !== '' ? "Relevant lexical matches:\n{$summary}" : null;
    }
}
