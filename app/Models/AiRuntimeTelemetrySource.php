<?php

namespace App\Models;

use Database\Factories\AiRuntimeTelemetrySourceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRuntimeTelemetrySource extends Model
{
    /** @use HasFactory<AiRuntimeTelemetrySourceFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'telemetry_run_id',
        'organization_id',
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
        return $this->belongsTo(AiRuntimeTelemetryRun::class, 'telemetry_run_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
