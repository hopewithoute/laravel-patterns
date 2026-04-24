<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Contracts\AiStreamSink;
use App\AI\Runtime\Streaming\AiStreamEnvelopeFactory;
use App\AI\Runtime\Streaming\RedisAiStreamOutput;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RedisAiStreamOutputTest extends TestCase
{
    public function test_it_returns_a_normalized_subscription_descriptor_response(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('publish')
            ->twice()
            ->withArgs(function (string $channel, string $payload): bool {
                return $channel === 'workspace-ai:session-123'
                    && is_string($payload)
                    && $payload !== '';
            });

        Redis::shouldReceive('connection')
            ->twice()
            ->with('cache')
            ->andReturn($connection);

        $response = (new RedisAiStreamOutput(
            envelopeFactory: new AiStreamEnvelopeFactory,
            connection: 'cache',
            channelPrefix: 'workspace-ai',
            debugMode: false,
        ))->respond(function (AiStreamSink $sink): void {
            $sink->publish([
                'type' => 'stream_start',
            ]);
        }, [
            'session_id' => 'session-123',
        ]);

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertSame([
            'stream' => [
                'driver' => 'redis',
                'session_id' => 'session-123',
                'subscription' => [
                    'channel' => 'workspace-ai:session-123',
                ],
                'meta' => [
                    'connection' => 'cache',
                ],
            ],
        ], $response->getData(true));
    }
}
