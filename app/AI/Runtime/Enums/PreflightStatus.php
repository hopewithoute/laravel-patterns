<?php

namespace App\AI\Runtime\Enums;

enum PreflightStatus: string
{
    case Allow = 'allow';
    case Reject = 'reject';
}
