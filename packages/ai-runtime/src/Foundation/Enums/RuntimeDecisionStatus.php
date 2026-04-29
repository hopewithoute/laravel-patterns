<?php

namespace Labtime\AiRuntime\Foundation\Enums;

enum RuntimeDecisionStatus: string
{
    case Allow = 'allow';
    case Reject = 'reject';
}
