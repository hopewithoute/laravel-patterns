<?php

namespace App\AI\Runtime\Contracts;

use Throwable;

interface AiStreamSink
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(array $payload): void;

    public function error(Throwable $exception): void;

    public function close(): void;
}
