<?php

namespace App\AI\Runtime\Enums;

enum CompletionMode: string
{
    case AgentStream = 'agent_stream';
    case ManualRejection = 'manual_rejection';
    case ManualReply = 'manual_reply';
}
