<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Streaming\AiStreamEnvelopeFactory;
use App\AI\Runtime\Streaming\AiStreamTransportRegistry;
use App\AI\Runtime\Streaming\MercureAiStreamOutput;
use App\AI\Runtime\Streaming\RedisAiStreamOutput;
use App\AI\Runtime\Streaming\SseAiStreamOutput;
use Tests\TestCase;

class AiStreamTransportRegistryTest extends TestCase
{
    public function test_it_resolves_the_default_sse_transport(): void
    {
        $registry = new AiStreamTransportRegistry(
            envelopeFactory: new AiStreamEnvelopeFactory,
            config: ['driver' => 'sse'],
            debugMode: true,
        );

        $this->assertInstanceOf(SseAiStreamOutput::class, $registry->resolve());
    }

    public function test_it_resolves_the_mercure_transport(): void
    {
        $registry = new AiStreamTransportRegistry(
            envelopeFactory: new AiStreamEnvelopeFactory,
            config: [
                'driver' => 'mercure',
                'mercure' => [
                    'hub_url' => 'https://mercure.test/.well-known/mercure',
                    'jwt' => 'token',
                    'topic_prefix' => 'workspace-ai',
                ],
            ],
            debugMode: true,
        );

        $this->assertInstanceOf(MercureAiStreamOutput::class, $registry->resolve());
    }

    public function test_it_resolves_the_redis_transport(): void
    {
        $registry = new AiStreamTransportRegistry(
            envelopeFactory: new AiStreamEnvelopeFactory,
            config: [
                'driver' => 'redis',
                'redis' => [
                    'connection' => 'cache',
                    'channel_prefix' => 'workspace-ai',
                ],
            ],
            debugMode: false,
        );

        $this->assertInstanceOf(RedisAiStreamOutput::class, $registry->resolve());
    }

    public function test_it_throws_for_unknown_transports(): void
    {
        $registry = new AiStreamTransportRegistry(
            envelopeFactory: new AiStreamEnvelopeFactory,
            config: ['driver' => 'unknown'],
            debugMode: false,
        );

        $this->expectException(\InvalidArgumentException::class);

        $registry->resolve();
    }
}
