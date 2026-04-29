<?php

namespace Labtime\AiRuntime\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RuntimeRunRecording extends Model
{
    protected $table = 'ai_runtime_run_recordings';

    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'session_id',
        'assistant_message_id',
        'conversation_id',
        'run_id',
        'journal',
    ];

    protected function casts(): array
    {
        return [
            'journal' => 'array',
        ];
    }
}
