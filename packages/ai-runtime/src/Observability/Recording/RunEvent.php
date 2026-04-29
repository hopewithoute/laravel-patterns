<?php

namespace Labtime\AiRuntime\Observability\Recording;

readonly class RunEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $sequence,
        public string $occurredAt,
        public int $offsetMs,
        public string $kind,
        public ?string $runId = null,
        public ?string $callId = null,
        public ?string $operation = null,
        public ?string $status = null,
        public array $payload = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sequence' => $this->sequence,
            'occurred_at' => $this->occurredAt,
            'offset_ms' => $this->offsetMs,
            'kind' => $this->kind,
            'run_id' => $this->runId,
            'call_id' => $this->callId,
            'operation' => $this->operation,
            'status' => $this->status,
            'payload' => $this->payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            sequence: (int) ($attributes['sequence'] ?? 0),
            occurredAt: (string) ($attributes['occurred_at'] ?? now()->toISOString()),
            offsetMs: (int) ($attributes['offset_ms'] ?? 0),
            kind: (string) ($attributes['kind'] ?? ''),
            runId: is_string($attributes['run_id'] ?? null) ? $attributes['run_id'] : null,
            callId: is_string($attributes['call_id'] ?? null) ? $attributes['call_id'] : null,
            operation: is_string($attributes['operation'] ?? null) ? $attributes['operation'] : null,
            status: is_string($attributes['status'] ?? null) ? $attributes['status'] : null,
            payload: is_array($attributes['payload'] ?? null) ? $attributes['payload'] : [],
        );
    }
}
