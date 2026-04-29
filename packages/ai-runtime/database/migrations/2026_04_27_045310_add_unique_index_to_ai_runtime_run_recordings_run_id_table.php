<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_runtime_run_recordings')) {
            return;
        }

        Schema::table('ai_runtime_run_recordings', function (Blueprint $table) {
            $table->unique('run_id', 'ai_runtime_recordings_run_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_runtime_run_recordings')) {
            return;
        }

        Schema::table('ai_runtime_run_recordings', function (Blueprint $table) {
            $table->dropUnique('ai_runtime_recordings_run_id_unique');
        });
    }
};
