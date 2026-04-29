<?php

namespace Labtime\AiRuntime\Foundation\Enums;

enum AiStreamEvent: string
{
    case StreamStart = 'stream_start';
    case StreamMetadata = 'stream_metadata';
    case StreamEnd = 'stream_end';
    case TextDelta = 'text_delta';
    case ToolCall = 'tool_call';
    case ToolResult = 'tool_result';
    case Artifact = 'artifact';
    case Error = 'error';
}
