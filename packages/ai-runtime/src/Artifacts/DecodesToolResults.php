<?php

namespace Labtime\AiRuntime\Artifacts;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Labtime\AiRuntime\Tools\ToolExecutionResult;

trait DecodesToolResults
{
    protected function resolveToolId(ToolExecutionResult $toolResult): string
    {
        $toolId = Arr::get($toolResult->metadata, 'tool_id');

        return is_string($toolId) && $toolId !== '' ? $toolId : Str::uuid()->toString();
    }

    protected function decodeResult(mixed $result): mixed
    {
        if (! is_string($result)) {
            return $result;
        }

        $trimmed = trim($result);

        if ($trimmed === '') {
            return $result;
        }

        try {
            return json_decode($trimmed, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $result;
        }
    }
}
