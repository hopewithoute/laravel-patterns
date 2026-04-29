<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Closure;
use Symfony\Component\HttpFoundation\Response;

interface AiStreamOutput
{
    /**
     * @param  Closure(AiStreamSink): void  $producer
     * @param  array<string, mixed>  $metadata
     */
    public function respond(Closure $producer, array $metadata = []): Response;
}
