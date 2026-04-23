<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('CREATE VIRTUAL TABLE IF NOT EXISTS ai_knowledge_chunk_fts USING fts5 (
            chunk_id UNINDEXED,
            knowledge_source_id UNINDEXED,
            organization_id UNINDEXED,
            project_id UNINDEXED,
            title,
            content
        )');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS ai_knowledge_chunk_fts');
    }
};
