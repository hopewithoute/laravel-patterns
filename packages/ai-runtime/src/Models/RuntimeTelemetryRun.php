<?php

namespace Labtime\AiRuntime\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuntimeTelemetryRun extends Model
{
    protected $table = 'ai_runtime_telemetry_runs';

    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'actor_id',
        'session_id',
        'conversation_id',
        'assistant_message_id',
        'intent',
        'decision',
        'risk_level',
        'completion_mode',
        'provider',
        'model',
        'retrieval_strategy',
        'retrieval_required',
        'retrieval_documents_count',
        'retrieval_sources',
        'tools_count',
        'tool_failed_count',
        'artifacts_count',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'decision_meta',
        'tool_summary',
        'retrieval_summary',
        'usage',
        'trace',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'retrieval_required' => 'boolean',
            'retrieval_documents_count' => 'integer',
            'retrieval_sources' => 'array',
            'tools_count' => 'integer',
            'tool_failed_count' => 'integer',
            'artifacts_count' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'decision_meta' => 'array',
            'tool_summary' => 'array',
            'retrieval_summary' => 'array',
            'usage' => 'array',
            'trace' => 'array',
            'meta' => 'array',
        ];
    }

    public function sources(): HasMany
    {
        return $this->hasMany(RuntimeTelemetrySource::class, 'telemetry_run_id');
    }
}
