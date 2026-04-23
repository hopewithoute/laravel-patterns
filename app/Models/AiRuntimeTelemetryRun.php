<?php

namespace App\Models;

use Database\Factories\AiRuntimeTelemetryRunFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiRuntimeTelemetryRun extends Model
{
    /** @use HasFactory<AiRuntimeTelemetryRunFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'user_id',
        'ai_chat_session_id',
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
        'preflight_meta',
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
            'preflight_meta' => 'array',
            'tool_summary' => 'array',
            'retrieval_summary' => 'array',
            'usage' => 'array',
            'trace' => 'array',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AiRuntimeTelemetrySource::class, 'telemetry_run_id');
    }
}
