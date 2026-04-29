<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_runtime_telemetry_runs', function (Blueprint $table) {
            $table->dropIndex('ai_runtime_runs_tenant_profile_created_idx');
            $table->dropColumn([
                'retrieval_profile',
                'retrieval_citations_count',
                'fused',
                'reranked',
            ]);
        });

        Schema::table('ai_runtime_telemetry_sources', function (Blueprint $table) {
            $table->dropColumn([
                'projects_count',
                'tasks_count',
                'weight',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ai_runtime_telemetry_runs', function (Blueprint $table) {
            $table->string('retrieval_profile', 50)->nullable()->after('model');
            $table->unsignedSmallInteger('retrieval_citations_count')->default(0)->after('retrieval_documents_count');
            $table->boolean('fused')->default(false)->after('retrieval_sources');
            $table->boolean('reranked')->default(false)->after('fused');

            $table->index(['tenant_id', 'retrieval_profile', 'created_at'], 'ai_runtime_runs_tenant_profile_created_idx');
        });

        Schema::table('ai_runtime_telemetry_sources', function (Blueprint $table) {
            $table->unsignedSmallInteger('projects_count')->default(0)->after('documents_count');
            $table->unsignedSmallInteger('tasks_count')->default(0)->after('projects_count');
            $table->decimal('weight', 8, 4)->nullable()->after('driver');
        });
    }
};
