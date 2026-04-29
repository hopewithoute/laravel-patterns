<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_runtime_run_recordings', function (Blueprint $table) {
            $table->string('assistant_message_id', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_runtime_run_recordings', function (Blueprint $table) {
            $table->string('assistant_message_id', 100)->nullable(false)->change();
        });
    }
};
