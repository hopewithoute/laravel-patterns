<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_runtime_telemetry_runs')) {
            return;
        }

        foreach (DB::table('ai_runtime_telemetry_runs')->orderBy('created_at')->cursor() as $run) {
            $meta = json_decode($run->meta ?? '[]', true);

            if (! is_array($meta) || ! array_key_exists('recording', $meta)) {
                continue;
            }

            unset($meta['recording']);

            DB::table('ai_runtime_telemetry_runs')
                ->where('id', $run->id)
                ->update([
                    'meta' => json_encode($meta, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_runtime_telemetry_runs') || ! Schema::hasTable('ai_runtime_run_recordings')) {
            return;
        }

        foreach (DB::table('ai_runtime_run_recordings')->orderBy('created_at')->cursor() as $recording) {
            if (! is_string($recording->assistant_message_id) || $recording->assistant_message_id === '') {
                continue;
            }

            $telemetryRun = DB::table('ai_runtime_telemetry_runs')
                ->where('assistant_message_id', $recording->assistant_message_id)
                ->latest('created_at')
                ->first(['id', 'meta']);

            if ($telemetryRun === null) {
                continue;
            }

            $meta = json_decode($telemetryRun->meta ?? '[]', true);

            if (! is_array($meta) || array_key_exists('recording', $meta)) {
                continue;
            }

            $journal = json_decode($recording->journal ?? '[]', true);

            if (! is_array($journal) || $journal === []) {
                continue;
            }

            $meta['recording'] = $journal;

            DB::table('ai_runtime_telemetry_runs')
                ->where('id', $telemetryRun->id)
                ->update([
                    'meta' => json_encode($meta, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }
};
