<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Streaming\AiStreamSubscriptionDescriptor;
use Tests\TestCase;

class AiStreamSubscriptionDescriptorTest extends TestCase
{
    public function test_it_serializes_a_mercure_subscription_descriptor(): void
    {
        $descriptor = AiStreamSubscriptionDescriptor::mercure(
            sessionId: 'session-123',
            topic: 'workspace-ai/session-123',
            hubUrl: 'https://mercure.test/.well-known/mercure',
        );

        $this->assertSame([
            'stream' => [
                'driver' => 'mercure',
                'session_id' => 'session-123',
                'subscription' => [
                    'topic' => 'workspace-ai/session-123',
                ],
                'meta' => [
                    'hub_url' => 'https://mercure.test/.well-known/mercure',
                ],
            ],
        ], $descriptor->toArray());
    }

    public function test_it_serializes_a_redis_subscription_descriptor_without_optional_meta(): void
    {
        $descriptor = AiStreamSubscriptionDescriptor::redis(
            sessionId: 'session-123',
            channel: 'workspace-ai:session-123',
        );

        $this->assertSame([
            'stream' => [
                'driver' => 'redis',
                'session_id' => 'session-123',
                'subscription' => [
                    'channel' => 'workspace-ai:session-123',
                ],
            ],
        ], $descriptor->toArray());
    }
}
