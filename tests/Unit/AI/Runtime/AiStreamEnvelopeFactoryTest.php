<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Enums\AiStreamEvent;
use App\AI\Runtime\Streaming\AiStreamEnvelopeFactory;
use Tests\TestCase;

class AiStreamEnvelopeFactoryTest extends TestCase
{
    public function test_it_builds_manual_stream_envelopes(): void
    {
        $factory = new AiStreamEnvelopeFactory;

        $this->assertSame([
            'type' => AiStreamEvent::StreamStart->value,
            'provider' => 'cliproxyapi',
            'model' => 'gpt-5.4-mini',
        ], $factory->streamStart('cliproxyapi', 'gpt-5.4-mini'));

        $this->assertSame([
            'type' => AiStreamEvent::TextDelta->value,
            'delta' => 'hello',
        ], $factory->textDelta('hello'));

        $this->assertSame([
            'type' => AiStreamEvent::StreamEnd->value,
            'usage' => [],
        ], $factory->streamEnd());

        $this->assertSame([
            'type' => 'done',
        ], $factory->done());
    }

    public function test_it_hides_exception_details_when_debug_is_disabled(): void
    {
        $factory = new AiStreamEnvelopeFactory;
        $payload = $factory->error(new \RuntimeException('Boom.'), false);

        $this->assertSame(AiStreamEvent::Error->value, $payload['type']);
        $this->assertSame('Unable to stream assistant response.', $payload['message']);
        $this->assertArrayNotHasKey('exception', $payload);
    }
}
