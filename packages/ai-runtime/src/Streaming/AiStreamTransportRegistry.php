<?php

namespace Labtime\AiRuntime\Streaming;

use InvalidArgumentException;
use Labtime\AiRuntime\Foundation\Contracts\AiStreamOutput;

readonly class AiStreamTransportRegistry
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private AiStreamEnvelopeFactory $envelopeFactory,
        private array $config = [],
        private bool $debugMode = false,
    ) {}

    public function resolve(?string $driver = null): AiStreamOutput
    {
        return match ($driver ?? $this->defaultDriver()) {
            'mercure' => new MercureAiStreamOutput(
                envelopeFactory: $this->envelopeFactory,
                publishUrl: $this->mercureConfig('publish_url', $this->mercureConfig('hub_url')),
                subscribeUrl: $this->mercureConfig(
                    'subscribe_url',
                    $this->mercureConfig('hub_url', '/.well-known/mercure'),
                ),
                jwt: $this->mercureConfig('jwt'),
                topicPrefix: (string) $this->mercureConfig('topic_prefix', 'ai-runtime'),
                debugMode: $this->debugMode,
            ),
            'redis' => new RedisAiStreamOutput(
                envelopeFactory: $this->envelopeFactory,
                connection: $this->redisConfig('connection'),
                channelPrefix: (string) $this->redisConfig('channel_prefix', 'ai-runtime'),
                debugMode: $this->debugMode,
            ),
            'sse' => new SseAiStreamOutput(
                envelopeFactory: $this->envelopeFactory,
                debugMode: $this->debugMode,
            ),
            default => throw new InvalidArgumentException('Unsupported AI stream driver.'),
        };
    }

    private function defaultDriver(): string
    {
        return (string) ($this->config['driver'] ?? 'sse');
    }

    private function mercureConfig(string $key, mixed $default = null): mixed
    {
        return $this->config['mercure'][$key] ?? $default;
    }

    private function redisConfig(string $key, mixed $default = null): mixed
    {
        return $this->config['redis'][$key] ?? $default;
    }
}
