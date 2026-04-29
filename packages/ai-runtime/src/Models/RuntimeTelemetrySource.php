<?php

namespace Labtime\AiRuntime\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuntimeTelemetrySource extends Model
{
    protected $table = 'ai_runtime_telemetry_sources';

    use HasUuids;

    protected $fillable = [
        'telemetry_run_id',
        'tenant_id',
        'source_key',
        'documents_count',
        'driver',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'documents_count' => 'integer',
            'meta' => 'array',
        ];
    }

    public function telemetryRun(): BelongsTo
    {
        return $this->belongsTo(RuntimeTelemetryRun::class, 'telemetry_run_id');
    }
}
