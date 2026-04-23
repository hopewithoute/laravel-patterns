<?php

namespace App\AI\Runtime\Hooks;

use App\AI\Runtime\Contracts\PostRunHook;
use App\AI\Runtime\Enums\PreflightStatus;
use App\AI\Runtime\Enums\RiskLevel;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Hooks\Attributes\RuntimeHook;
use Illuminate\Support\Facades\Log;

#[RuntimeHook(priority: 20)]
class LogRuntimeGovernanceHook implements PostRunHook
{
    public function handle(RuntimeRunReport $report): void
    {
        $toolSummary = $report->toolSummary();
        $retrievalSummary = $report->retrievalSummary();
        $shouldLog = $report->decision->status !== PreflightStatus::Allow
            || $report->decision->riskLevel !== RiskLevel::Low
            || $toolSummary['failed_count'] > 0
            || $retrievalSummary['documents_count'] > 0;

        if (! $shouldLog) {
            return;
        }

        Log::notice('AI runtime governance signal detected.', [
            'intent' => $report->decision->intent,
            'decision' => $report->decision->status,
            'risk_level' => $report->decision->riskLevel,
            'reasons' => $report->decision->reasons,
            'preflight_metadata' => $report->decision->metadata,
            'retrieval_strategy' => $retrievalSummary['strategy'],
            'completion_mode' => $report->completionMode,
            'tool_summary' => $toolSummary,
            'retrieval_summary' => $retrievalSummary,
            'conversation_id' => $report->conversationId,
        ]);
    }
}
