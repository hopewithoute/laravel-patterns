<?php

namespace App\AI\Runtime\Retrieval;

readonly class RetrievalResult
{
    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?string $query = null,
        public array $documents = [],
        public array $metadata = [],
    ) {}

    public static function empty(): self
    {
        return new self;
    }
}
