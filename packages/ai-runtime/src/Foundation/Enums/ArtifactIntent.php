<?php

namespace Labtime\AiRuntime\Foundation\Enums;

enum ArtifactIntent: string
{
    case Auto = 'auto';
    case None = 'none';
    case TaskSummary = 'task_summary';
    case ApprovalCard = 'approval_card';
    case StatsCard = 'stats_card';
    case Update = 'update';
}
