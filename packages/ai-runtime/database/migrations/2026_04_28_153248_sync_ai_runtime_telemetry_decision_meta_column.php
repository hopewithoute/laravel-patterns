<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_runtime_telemetry_runs')) {
            return;
        }

        $hasDecisionMeta = Schema::hasColumn('ai_runtime_telemetry_runs', 'decision_meta');
        $hasPreflightMeta = Schema::hasColumn('ai_runtime_telemetry_runs', 'preflight_meta');

        if (! $hasDecisionMeta && $hasPreflightMeta) {
            Schema::table('ai_runtime_telemetry_runs', function (Blueprint $table) {
                $table->renameColumn('preflight_meta', 'decision_meta');
            });

            return;
        }

        if (! $hasDecisionMeta) {
            Schema::table('ai_runtime_telemetry_runs', function (Blueprint $table) {
                $table->json('decision_meta')->nullable()->after('total_tokens');
            });

            return;
        }

        if ($hasPreflightMeta) {
            DB::table('ai_runtime_telemetry_runs')
                ->whereNull('decision_meta')
                ->whereNotNull('preflight_meta')
                ->update([
                    'decision_meta' => DB::raw('preflight_meta'),
                ]);

            Schema::table('ai_runtime_telemetry_runs', function (Blueprint $table) {
                $table->dropColumn('preflight_meta');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_runtime_telemetry_runs')) {
            return;
        }

        if (Schema::hasColumn('ai_runtime_telemetry_runs', 'decision_meta')
            && ! Schema::hasColumn('ai_runtime_telemetry_runs', 'preflight_meta')) {
            Schema::table('ai_runtime_telemetry_runs', function (Blueprint $table) {
                $table->renameColumn('decision_meta', 'preflight_meta');
            });
        }
    }
};
