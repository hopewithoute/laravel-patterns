<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runtime_telemetry_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36)->index();
            $table->string('actor_id', 36)->nullable()->index();
            $table->string('session_id', 36)->nullable()->index();
            $table->string('conversation_id', 100)->nullable();
            $table->string('assistant_message_id', 100)->nullable();
            $table->string('intent', 50);
            $table->string('decision', 20);
            $table->string('risk_level', 20);
            $table->string('completion_mode', 30);
            $table->string('provider', 50)->nullable();
            $table->string('model')->nullable();
            $table->string('retrieval_profile', 50)->nullable();
            $table->string('retrieval_strategy', 50)->nullable();
            $table->boolean('retrieval_required')->default(false);
            $table->unsignedSmallInteger('retrieval_documents_count')->default(0);
            $table->unsignedSmallInteger('retrieval_citations_count')->default(0);
            $table->json('retrieval_sources')->nullable();
            $table->boolean('fused')->default(false);
            $table->boolean('reranked')->default(false);
            $table->unsignedSmallInteger('tools_count')->default(0);
            $table->unsignedSmallInteger('tool_failed_count')->default(0);
            $table->unsignedSmallInteger('artifacts_count')->default(0);
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->json('decision_meta')->nullable();
            $table->json('tool_summary')->nullable();
            $table->json('retrieval_summary')->nullable();
            $table->json('usage')->nullable();
            $table->json('trace')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at'], 'ai_runtime_runs_tenant_created_idx');
            $table->index(['tenant_id', 'intent', 'created_at'], 'ai_runtime_runs_tenant_intent_created_idx');
            $table->index(['tenant_id', 'retrieval_profile', 'created_at'], 'ai_runtime_runs_tenant_profile_created_idx');
            $table->index(['tenant_id', 'decision', 'risk_level', 'created_at'], 'ai_runtime_runs_tenant_decision_risk_created_idx');
            $table->index(['session_id', 'created_at'], 'ai_runtime_runs_session_created_idx');
            $table->index(['conversation_id', 'created_at'], 'ai_runtime_runs_conversation_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runtime_telemetry_runs');
    }
};
