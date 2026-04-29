<?php

namespace Labtime\AiRuntime\Foundation\Enums;

enum ToolFailureBehavior: string
{
    case None = 'none';
    case AskUser = 'ask_user';
    case Retry = 'retry';
    case SurfaceToUser = 'surface_to_user';
}
