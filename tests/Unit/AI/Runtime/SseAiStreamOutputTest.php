<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Contracts\AiStreamSink;
use App\AI\Runtime\Streaming\AiStreamEnvelopeFactory;
use App\AI\Runtime\Streaming\SseAiStreamOutput;
use Tests\TestCase;

class SseAiStreamOutputTest extends TestCase
{
    public function test_it_publishes_error_events_before_done_when_the_producer_fails(): void
    {
        config(['app.debug' => true]);

        $response = (new SseAiStreamOutput(
            envelopeFactory: new AiStreamEnvelopeFactory,
            debugMode: true,
            flushOutput: false,
        ))->respond(function (AiStreamSink $sink): void {
            $sink->publish([
                'type' => 'stream_start',
            ]);

            throw new \RuntimeException('Transport exploded.');
        });

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertMatchesRegularExpression('/"type":"stream_start".*"type":"error".*Transport exploded\..*data: \[DONE\]/s', $content);
    }
}
