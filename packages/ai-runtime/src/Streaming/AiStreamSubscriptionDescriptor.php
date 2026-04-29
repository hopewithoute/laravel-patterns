<?php

namespace Labtime\AiRuntime\Streaming;

readonly class AiStreamSubscriptionDescriptor
{
    /**
     * @param  array<string, mixed>  $subscription
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        private string $driver,
        private string $sessionId,
        private array $subscription,
        private array $meta = [],
    ) {}

    public static function mercure(string $sessionId, string $topic, ?string $hubUrl = null): self
    {
        $meta = [];

        if (is_string($hubUrl) && $hubUrl !== '') {
            $meta['hub_url'] = $hubUrl;
        }

        return new self(
            driver: 'mercure',
            sessionId: $sessionId,
            subscription: ['topic' => $topic],
            meta: $meta,
        );
    }

    public static function redis(string $sessionId, string $channel, ?string $connection = null): self
    {
        $meta = [];

        if (is_string($connection) && $connection !== '') {
            $meta['connection'] = $connection;
        }

        return new self(
            driver: 'redis',
            sessionId: $sessionId,
            subscription: ['channel' => $channel],
            meta: $meta,
        );
    }

    /**
     * @return array{stream: array<string, mixed>}
     */
    public function toArray(): array
    {
        $stream = [
            'driver' => $this->driver,
            'session_id' => $this->sessionId,
            'subscription' => $this->subscription,
        ];

        if ($this->meta !== []) {
            $stream['meta'] = $this->meta;
        }

        return ['stream' => $stream];
    }
}
