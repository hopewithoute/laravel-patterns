<?php

namespace App\AI\Runtime\Telemetry;

use App\AI\Runtime\Contracts\TelemetryStore;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\Models\AiRuntimeTelemetryRun;
use App\Models\AiRuntimeTelemetrySource;
use Illuminate\Support\Facades\DB;

readonly class DatabaseTelemetryStore implements TelemetryStore
{
    public function __construct(
        private AiRuntimeTelemetryRun $telemetryRun,
        private AiRuntimeTelemetrySource $telemetrySource,
    ) {}

    public function store(RuntimeRunReport $report): ?AiRuntimeTelemetryRun
    {
        return DB::transaction(function () use ($report): AiRuntimeTelemetryRun {
            $retrievalSummary = $report->retrievalSummary();
            $toolSummary = $report->toolSummary();

            $run = $this->telemetryRun->newQuery()->create([
                'organization_id' => $report->context->organization->id,
                'user_id' => $report->context->user->id,
                'ai_chat_session_id' => $report->context->session?->id,
                'conversation_id' => $report->conversationId,
                'assistant_message_id' => $report->assistantMessageId,
                'intent' => $report->decision->intent->value,
                'decision' => $report->decision->status->value,
                'risk_level' => $report->decision->riskLevel->value,
                'completion_mode' => $report->completionMode->value,
                'provider' => $report->providerMeta['provider'] ?? $report->context->provider,
                'model' => $report->providerMeta['model'] ?? $report->context->model,
                'retrieval_strategy' => $retrievalSummary['strategy'],
                'retrieval_required' => $retrievalSummary['required'],
                'retrieval_documents_count' => $retrievalSummary['documents_count'],
                'retrieval_sources' => $retrievalSummary['sources'],
                'tools_count' => $toolSummary['count'],
                'tool_failed_count' => $toolSummary['failed_count'],
                'artifacts_count' => count($report->artifacts),
                'prompt_tokens' => $report->usage['prompt_tokens'] ?? null,
                'completion_tokens' => $report->usage['completion_tokens'] ?? null,
                'total_tokens' => $report->usage['total_tokens'] ?? null,
                'preflight_meta' => $report->decision->metadata,
                'tool_summary' => $toolSummary,
                'retrieval_summary' => $retrievalSummary,
                'usage' => $report->usage,
                'trace' => $report->trace,
                'meta' => [
                    'storage_driver' => $this->driverName(),
                    'storage_version' => 2,
                ],
            ]);

            $this->storeSources($run, $retrievalSummary);

            return $run->load('sources');
        });
    }

    public function driverName(): string
    {
        return 'database';
    }

    /**
     * @param  array<string, mixed>  $retrievalSummary
     */
    private function storeSources(AiRuntimeTelemetryRun $run, array $retrievalSummary): void
    {
        $sourceBreakdown = is_array($retrievalSummary['source_breakdown'] ?? null)
            ? $retrievalSummary['source_breakdown']
            : [];

        foreach ($sourceBreakdown as $sourceKey => $sourceMeta) {
            if (! is_string($sourceKey) || $sourceKey === '' || ! is_array($sourceMeta)) {
                continue;
            }

            $this->telemetrySource->newQuery()->create([
                'telemetry_run_id' => $run->id,
                'organization_id' => $run->organization_id,
                'source_key' => $sourceKey,
                'documents_count' => (int) ($sourceMeta['documents_count'] ?? 0),
                'driver' => $sourceMeta['driver'] ?? null,
                'meta' => [],
            ]);
        }
    }
}
