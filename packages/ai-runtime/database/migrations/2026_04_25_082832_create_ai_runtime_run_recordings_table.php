<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runtime_run_recordings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36)->index();
            $table->string('session_id', 36)->nullable()->index();
            $table->string('assistant_message_id', 100);
            $table->string('conversation_id', 100)->nullable();
            $table->string('run_id', 100)->nullable();
            $table->json('journal');
            $table->timestamps();

            $table->unique('assistant_message_id', 'ai_runtime_recordings_assistant_message_unique');
            $table->index(['session_id', 'assistant_message_id'], 'ai_runtime_recordings_session_message_idx');
            $table->index(['tenant_id', 'created_at'], 'ai_runtime_recordings_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runtime_run_recordings');
    }
};
