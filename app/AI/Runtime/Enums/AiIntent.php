<?php

namespace App\AI\Runtime\Enums;

enum AiIntent: string
{
    case TaskCreate = 'task_create';
    case WorkspaceLookup = 'workspace_lookup';
    case KnowledgeLookup = 'knowledge_lookup';
    case HybridLookup = 'hybrid_lookup';
    case WorkspaceChat = 'workspace_chat';
    case OutOfScope = 'out_of_scope';
    case GuardrailBlocked = 'guardrail_blocked';
    case InvalidPrompt = 'invalid_prompt';
    case WorkspaceAccessDenied = 'workspace_access_denied';
    case WorkspaceOperation = 'workspace_operation';
}
