<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Contracts\AiStreamSink;
use App\AI\Runtime\Streaming\AiStreamEnvelopeFactory;
use App\AI\Runtime\Streaming\MercureAiStreamOutput;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MercureAiStreamOutputTest extends TestCase
{
    public function test_it_returns_a_normalized_subscription_descriptor_response(): void
    {
        Http::fake([
            'https://mercure.test/.well-known/mercure' => Http::response('', Response::HTTP_OK),
        ]);

        $response = (new MercureAiStreamOutput(
            envelopeFactory: new AiStreamEnvelopeFactory,
            hubUrl: 'https://mercure.test/.well-known/mercure',
            jwt: 'stream-token',
            topicPrefix: 'workspace-ai',
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
                'driver' => 'mercure',
                'session_id' => 'session-123',
                'subscription' => [
                    'topic' => 'workspace-ai/session-123',
                ],
                'meta' => [
                    'hub_url' => 'https://mercure.test/.well-known/mercure',
                ],
            ],
        ], $response->getData(true));

        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://mercure.test/.well-known/mercure'
                && $request['topic'] === 'workspace-ai/session-123';
        });
    }
}
