<?php

namespace App\AI\Runtime\Retrieval;

readonly class RetrievalPlan
{
    /**
     * @param  array<int, string>  $sources
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $required = false,
        public ?string $query = null,
        public array $sources = [],
        public array $filters = [],
        public array $metadata = [],
    ) {}

    public static function none(): self
    {
        return new self;
    }
}
