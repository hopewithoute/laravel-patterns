<?php

namespace Database\Factories;

use App\Models\AiRuntimeTelemetryRun;
use App\Models\AiRuntimeTelemetrySource;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRuntimeTelemetrySource>
 */
class AiRuntimeTelemetrySourceFactory extends Factory
{
    protected $model = AiRuntimeTelemetrySource::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'telemetry_run_id' => AiRuntimeTelemetryRun::factory(),
            'organization_id' => Organization::factory(),
            'source_key' => 'vector_docs',
            'documents_count' => 2,
            'driver' => 'database',
            'meta' => ['driver' => 'database'],
        ];
    }
}
