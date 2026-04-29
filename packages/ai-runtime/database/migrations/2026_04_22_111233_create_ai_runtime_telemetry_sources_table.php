<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runtime_telemetry_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('telemetry_run_id')
                ->constrained('ai_runtime_telemetry_runs')
                ->cascadeOnDelete();
            $table->string('tenant_id', 36)->index();
            $table->string('source_key', 50);
            $table->unsignedSmallInteger('documents_count')->default(0);
            $table->unsignedSmallInteger('projects_count')->default(0);
            $table->unsignedSmallInteger('tasks_count')->default(0);
            $table->string('driver', 50)->nullable();
            $table->decimal('weight', 8, 4)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['telemetry_run_id', 'source_key'], 'ai_runtime_sources_run_source_unique');
            $table->index(['tenant_id', 'source_key', 'created_at'], 'ai_runtime_sources_tenant_source_created_idx');
            $table->index(['source_key', 'driver'], 'ai_runtime_sources_source_driver_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runtime_telemetry_sources');
    }
};
