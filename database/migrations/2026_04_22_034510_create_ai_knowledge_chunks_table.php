<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('knowledge_source_id')
                ->constrained('ai_knowledge_sources')
                ->cascadeOnDelete();
            $table->foreignUuid('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUuid('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->unsignedInteger('token_count')->default(0);
            $table->string('content_hash', 64);
            $table->string('embedding_provider')->nullable();
            $table->string('embedding_model')->nullable();
            $table->string('vector_store')->nullable();
            $table->string('vector_namespace')->nullable();
            $table->string('vector_document_id')->nullable();
            $table->string('vector_chunk_id')->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['knowledge_source_id', 'chunk_index'], 'ai_knowledge_chunks_source_chunk_unique');
            $table->index(['organization_id', 'project_id'], 'ai_knowledge_chunks_org_project_idx');
            $table->index(['knowledge_source_id', 'embedded_at'], 'ai_knowledge_chunks_source_embedded_idx');
            $table->index(['vector_store', 'vector_namespace'], 'ai_knowledge_chunks_store_namespace_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_chunks');
    }
};
