<?php

namespace Tests\Feature;

use Laravel\Ai\Ai;
use Tests\TestCase;

class AiConnectionTest extends TestCase
{
    public function test_ai_connection(): void
    {
        try {
            $response = \Laravel\Ai\agent()->prompt(
                prompt: 'Hello, are you there?',
                provider: 'deepseek',
                model: 'blackbox-grok-fast'
            );
            
            dump($response->text);
            
            $this->assertNotNull($response->text);
        } catch (\Exception $e) {
            dump($e->getMessage());
            $this->fail("Connection failed: " . $e->getMessage());
        }
    }
}
