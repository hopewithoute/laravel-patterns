<?php

namespace App\AI\Runtime\Retrieval;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\KnowledgeSource;

readonly class WorkspaceBasicKnowledgeSource implements KnowledgeSource
{
    /**
     * @param  iterable<KnowledgeSource>  $sources
     */
    public function __construct(
        private iterable $sources,
    ) {}

    public function supports(RetrievalPlan $plan): bool
    {
        return collect($this->sources)->contains(fn (KnowledgeSource $source): bool => $source->supports($plan));
    }

    public function retrieve(AiRuntimeContext $context, RetrievalPlan $plan): RetrievalResult
    {
        $results = collect($this->sources)
            ->filter(fn (KnowledgeSource $source): bool => $source->supports($plan))
            ->map(fn (KnowledgeSource $source): RetrievalResult => $source->retrieve($context, $plan))
            ->values();

        if ($results->isEmpty()) {
            return RetrievalResult::empty();
        }

        $documents = $results
            ->flatMap(fn (RetrievalResult $result): array => $result->documents)
            ->unique(fn (array $document): string => ($document['type'] ?? 'unknown').':'.($document['id'] ?? 'unknown'))
            ->values()
            ->all();
        $summary = $results
            ->map(fn (RetrievalResult $result): ?string => $result->metadata['summary'] ?? null)
            ->filter(fn (?string $value): bool => is_string($value) && trim($value) !== '')
            ->implode("\n\n");
        $sources = $results
            ->flatMap(function (RetrievalResult $result): array {
                $sources = $result->metadata['sources'] ?? [];

                return is_array($sources) ? $sources : [];
            })
            ->filter(fn (mixed $source): bool => is_string($source) && $source !== '')
            ->unique()
            ->values()
            ->all();
        $sourceBreakdown = $results
            ->mapWithKeys(function (RetrievalResult $result): array {
                $sourceKey = $result->metadata['sources'][0] ?? null;

                if (! is_string($sourceKey) || $sourceKey === '') {
                    return [];
                }

                return [
                    $sourceKey => [
                        'documents_count' => (int) ($result->metadata['documents_count'] ?? count($result->documents)),
                        'driver' => $result->metadata['driver'] ?? null,
                    ],
                ];
            })
            ->all();

        return new RetrievalResult(
            query: $plan->query ?? $context->prompt,
            documents: $documents,
            metadata: [
                'summary' => $summary !== '' ? $summary : null,
                'sources' => $sources,
                'documents_count' => count($documents),
                'source_breakdown' => $sourceBreakdown,
            ],
        );
    }
}
