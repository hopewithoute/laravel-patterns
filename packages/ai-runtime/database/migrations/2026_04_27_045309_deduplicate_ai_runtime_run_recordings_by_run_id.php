<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_runtime_run_recordings')) {
            return;
        }

        $duplicateRunIds = DB::table('ai_runtime_run_recordings')
            ->select('run_id')
            ->whereNotNull('run_id')
            ->groupBy('run_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('run_id');

        foreach ($duplicateRunIds as $runId) {
            $recordingIds = DB::table('ai_runtime_run_recordings')
                ->where('run_id', $runId)
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->pluck('id');

            $recordingIdsToDelete = $recordingIds->slice(1)->all();

            if ($recordingIdsToDelete === []) {
                continue;
            }

            DB::table('ai_runtime_run_recordings')
                ->whereIn('id', $recordingIdsToDelete)
                ->delete();
        }
    }

    public function down(): void
    {
        //
    }
};
