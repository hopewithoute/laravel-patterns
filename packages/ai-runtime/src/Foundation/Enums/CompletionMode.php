<?php

namespace Labtime\AiRuntime\Foundation\Enums;

enum CompletionMode: string
{
    case AgentStream = 'agent_stream';
    case StreamError = 'stream_error';
    case ManualRejection = 'manual_rejection';
    case ManualReply = 'manual_reply';
}
