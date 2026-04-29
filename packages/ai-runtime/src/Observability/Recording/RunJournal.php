<?php

namespace Labtime\AiRuntime\Observability\Recording;

readonly class RunJournal
{
    /**
     * @param  array<int, RunEvent>  $events
     */
    public function __construct(
        public ?string $runId = null,
        public ?string $conversationId = null,
        public ?string $sessionId = null,
        public ?string $tenantId = null,
        public ?string $actorId = null,
        public ?string $requestId = null,
        public ?string $provider = null,
        public ?string $model = null,
        public ?string $startedAt = null,
        public ?string $finishedAt = null,
        public string $status = 'idle',
        public array $events = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'conversation_id' => $this->conversationId,
            'session_id' => $this->sessionId,
            'tenant_id' => $this->tenantId,
            'actor_id' => $this->actorId,
            'request_id' => $this->requestId,
            'provider' => $this->provider,
            'model' => $this->model,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'status' => $this->status,
            'events' => array_map(
                fn (RunEvent $event): array => $event->toArray(),
                $this->events,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $events = is_array($attributes['events'] ?? null) ? $attributes['events'] : [];

        return new self(
            runId: is_string($attributes['run_id'] ?? null) ? $attributes['run_id'] : null,
            conversationId: is_string($attributes['conversation_id'] ?? null) ? $attributes['conversation_id'] : null,
            sessionId: is_string($attributes['session_id'] ?? null) ? $attributes['session_id'] : null,
            tenantId: is_string($attributes['tenant_id'] ?? null)
                ? $attributes['tenant_id']
                : (is_string($attributes['organization_id'] ?? null) ? $attributes['organization_id'] : null),
            actorId: is_string($attributes['actor_id'] ?? null)
                ? $attributes['actor_id']
                : (is_string($attributes['user_id'] ?? null) ? $attributes['user_id'] : null),
            requestId: is_string($attributes['request_id'] ?? null) ? $attributes['request_id'] : null,
            provider: is_string($attributes['provider'] ?? null) ? $attributes['provider'] : null,
            model: is_string($attributes['model'] ?? null) ? $attributes['model'] : null,
            startedAt: is_string($attributes['started_at'] ?? null) ? $attributes['started_at'] : null,
            finishedAt: is_string($attributes['finished_at'] ?? null) ? $attributes['finished_at'] : null,
            status: is_string($attributes['status'] ?? null) ? $attributes['status'] : 'idle',
            events: array_values(array_map(
                fn (array $event): RunEvent => RunEvent::fromArray($event),
                array_values(array_filter($events, 'is_array')),
            )),
        );
    }
}
