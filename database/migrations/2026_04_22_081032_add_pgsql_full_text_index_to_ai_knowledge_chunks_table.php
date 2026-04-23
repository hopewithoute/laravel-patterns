<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $language = str_replace("'", "''", (string) config('ai.runtime.lexical.language', 'simple'));

        DB::statement(
            "CREATE INDEX IF NOT EXISTS ai_knowledge_chunks_lexical_fts_idx
            ON ai_knowledge_chunks
            USING GIN (to_tsvector('{$language}', concat_ws(' ', coalesce(content, ''), coalesce(meta->>'title', ''))))"
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS ai_knowledge_chunks_lexical_fts_idx');
    }
};
