<?php

namespace Labtime\AiRuntime\Tools;

class ToolExecutionJournal
{
    /**
     * @var array<int, ToolExecutionResult>
     */
    private array $results = [];

    private int $cursor = 0;

    public function record(ToolExecutionResult $result): void
    {
        $this->results[] = $result;
    }

    /**
     * @return array<int, ToolExecutionResult>
     */
    public function all(): array
    {
        return $this->results;
    }

    public function next(): ?ToolExecutionResult
    {
        $result = $this->results[$this->cursor] ?? null;

        if ($result !== null) {
            $this->cursor++;
        }

        return $result;
    }

    public function hasRecordedResults(): bool
    {
        return $this->results !== [];
    }
}
