<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUuid('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('source_type', 50);
            $table->string('title');
            $table->longText('content');
            $table->string('reference_uri')->nullable();
            $table->string('status', 25)->default('pending');
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->timestamp('indexed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'ai_knowledge_sources_org_status_idx');
            $table->index(['organization_id', 'project_id'], 'ai_knowledge_sources_org_project_idx');
            $table->index(['source_type', 'status'], 'ai_knowledge_sources_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_sources');
    }
};
