<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_runtime_run_recordings') || ! Schema::hasTable('ai_runtime_telemetry_runs')) {
            return;
        }

        foreach (DB::table('ai_runtime_telemetry_runs')->orderBy('created_at')->cursor() as $run) {
            $meta = json_decode($run->meta ?? '[]', true);

            if (! is_array($meta)) {
                continue;
            }

            $recording = $meta['recording'] ?? null;

            if (! is_array($recording) || $recording === [] || ! is_string($run->assistant_message_id) || $run->assistant_message_id === '') {
                continue;
            }

            $existing = DB::table('ai_runtime_run_recordings')
                ->where('assistant_message_id', $run->assistant_message_id)
                ->exists();

            $attributes = [
                'tenant_id' => $run->tenant_id ?? $run->organization_id,
                'session_id' => $run->session_id ?? $run->ai_chat_session_id,
                'conversation_id' => $run->conversation_id,
                'run_id' => is_string($recording['run_id'] ?? null) ? $recording['run_id'] : null,
                'journal' => json_encode($recording, JSON_THROW_ON_ERROR),
                'updated_at' => $run->updated_at,
            ];

            if ($existing) {
                DB::table('ai_runtime_run_recordings')
                    ->where('assistant_message_id', $run->assistant_message_id)
                    ->update($attributes);

                continue;
            }

            DB::table('ai_runtime_run_recordings')->insert([
                ...$attributes,
                'id' => (string) Str::uuid(),
                'assistant_message_id' => $run->assistant_message_id,
                'created_at' => $run->created_at,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
