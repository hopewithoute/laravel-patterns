<?php

namespace Labtime\AiRuntime\Foundation\Enums;

enum ToolFailureType: string
{
    case UnknownError = 'unknown_error';
    case AiToolError = 'ai_tool_error';
    case ValidationError = 'validation_error';
    case AuthorizationError = 'authorization_error';
    case TransientError = 'transient_error';
    case DomainError = 'domain_error';
}
