<?php

namespace App\AI\Runtime\Hooks;

use App\AI\Runtime\Contracts\PostRunHook;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Hooks\Attributes\RuntimeHook;
use Illuminate\Support\Facades\Log;

#[RuntimeHook(priority: 10)]
class LogRuntimeSummaryHook implements PostRunHook
{
    public function handle(RuntimeRunReport $report): void
    {
        $retrievalSummary = $report->retrievalSummary();

        Log::info('AI runtime run completed.', [
            'intent' => $report->decision->intent,
            'decision' => $report->decision->status,
            'risk_level' => $report->decision->riskLevel,
            'workspace_id' => $report->context->organization->id,
            'user_id' => $report->context->user->id,
            'completion_mode' => $report->completionMode,
            'provider' => $report->providerMeta['provider'] ?? $report->context->provider,
            'model' => $report->providerMeta['model'] ?? $report->context->model,
            'tool_summary' => $report->toolSummary(),
            'artifacts_count' => count($report->artifacts),
            'retrieval_strategy' => $retrievalSummary['strategy'],
            'retrieval_summary' => $retrievalSummary,
            'usage' => $report->usage,
        ]);
    }
}
