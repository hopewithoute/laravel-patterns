<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('invite_code', 12)->unique()->nullable()->after('slug');
        });

        // Generate invite codes for existing organizations in a database-agnostic way.
        DB::table('organizations')
            ->select('id')
            ->whereNull('invite_code')
            ->orderBy('id')
            ->get()
            ->each(function (object $organization): void {
                do {
                    $inviteCode = Str::upper(Str::random(8));
                } while (DB::table('organizations')->where('invite_code', $inviteCode)->exists());

                DB::table('organizations')
                    ->where('id', $organization->id)
                    ->update(['invite_code' => $inviteCode]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('invite_code');
        });
    }
};
