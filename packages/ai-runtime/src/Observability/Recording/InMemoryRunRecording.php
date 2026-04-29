<?php

namespace Labtime\AiRuntime\Observability\Recording;

use Illuminate\Support\Str;
use Labtime\AiRuntime\Foundation\Contracts\RunRecording;

class InMemoryRunRecording implements RunRecording
{
    private ?RunStartData $startData = null;

    /**
     * @var array<int, RunEvent>
     */
    private array $events = [];

    /**
     * @var array<string, string>
     */
    private array $callOperations = [];

    /**
     * @var array<string, bool>
     */
    private array $finishedCalls = [];

    private int $sequence = 0;

    private ?int $startedAtMonotonic = null;

    private ?string $conversationId = null;

    private ?string $startedAt = null;

    private ?string $finishedAt = null;

    private string $status = 'idle';

    public function start(RunStartData $data): void
    {
        if ($this->startData instanceof RunStartData) {
            return;
        }

        $this->startData = $data;
        $this->startedAtMonotonic = hrtime(true);
        $this->conversationId = $data->conversationId;
        $this->startedAt = now()->toISOString();
        $this->status = 'running';

        $this->appendEvent(
            kind: 'run.started',
            payload: $data->toPayload(),
        );
    }

    public function setConversationId(?string $conversationId): void
    {
        $this->ensureStarted();
        $this->conversationId = $conversationId;
    }

    public function startCall(string $operation, array $meta = []): string
    {
        $this->ensureStarted();

        $callId = Str::uuid()->toString();
        $this->callOperations[$callId] = $operation;

        $this->appendEvent(
            kind: 'call.started',
            callId: $callId,
            operation: $operation,
            payload: [
                'name' => $operation,
                'input_meta' => $meta,
            ],
        );

        return $callId;
    }

    public function finishCall(string $callId, string $status = 'succeeded', array $meta = []): void
    {
        $this->ensureStarted();

        if (isset($this->finishedCalls[$callId])) {
            return;
        }

        $operation = $this->callOperations[$callId] ?? null;

        if ($operation === null) {
            throw new \LogicException("Run call [{$callId}] was never started.");
        }

        $this->finishedCalls[$callId] = true;

        $this->appendEvent(
            kind: 'call.finished',
            callId: $callId,
            operation: $operation,
            status: $status,
            payload: [
                'result_meta' => $meta,
                'error_code' => $meta['error_code'] ?? null,
                'error_message' => $meta['error_message'] ?? null,
            ],
        );
    }

    public function recordOutput(array $payload, ?string $callId = null): void
    {
        $this->ensureStarted();

        $this->appendEvent(
            kind: 'output.published',
            callId: $callId,
            operation: $callId !== null ? ($this->callOperations[$callId] ?? null) : null,
            payload: [
                'event_type' => (string) ($payload['type'] ?? 'unknown'),
                'event_payload' => $payload,
            ],
        );
    }

    public function finish(array $meta = []): void
    {
        $this->ensureStarted();

        if ($this->finishedAt !== null) {
            return;
        }

        $this->finishedAt = now()->toISOString();
        $this->status = $this->deriveRunStatus($meta);

        $this->appendEvent(
            kind: 'run.finished',
            status: $this->status,
            payload: [
                'completion_mode' => $meta['completion_mode'] ?? null,
                'usage' => $meta['usage'] ?? [],
                'result_meta' => $meta,
            ],
        );
    }

    public function journal(): RunJournal
    {
        return new RunJournal(
            runId: $this->startData?->runId,
            conversationId: $this->conversationId,
            sessionId: $this->startData?->sessionId,
            tenantId: $this->startData?->tenantId,
            actorId: $this->startData?->actorId,
            requestId: $this->startData?->requestId,
            provider: $this->startData?->provider,
            model: $this->startData?->model,
            startedAt: $this->startedAt,
            finishedAt: $this->finishedAt,
            status: $this->status,
            events: $this->events,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function appendEvent(
        string $kind,
        array $payload = [],
        ?string $callId = null,
        ?string $operation = null,
        ?string $status = null,
    ): void {
        $this->sequence++;

        $this->events[] = new RunEvent(
            sequence: $this->sequence,
            occurredAt: now()->toISOString(),
            offsetMs: $this->offsetMs(),
            kind: $kind,
            runId: $this->startData?->runId,
            callId: $callId,
            operation: $operation,
            status: $status,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function deriveRunStatus(array $meta): string
    {
        $explicitStatus = $meta['status'] ?? null;

        if (is_string($explicitStatus) && $explicitStatus !== '') {
            return $explicitStatus;
        }

        foreach ($this->events as $event) {
            if ($event->kind === 'call.finished' && $event->status === 'failed') {
                return 'failed';
            }
        }

        return 'completed';
    }

    private function ensureStarted(): void
    {
        if ($this->startData instanceof RunStartData) {
            return;
        }

        throw new \LogicException('Run recording must be started before recording events.');
    }

    private function offsetMs(): int
    {
        if ($this->startedAtMonotonic === null) {
            return 0;
        }

        return (int) round((hrtime(true) - $this->startedAtMonotonic) / 1_000_000);
    }
}
