<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing organization_id from users to pivot table
        $users = DB::table('users')
            ->whereNotNull('organization_id')
            ->get(['id', 'organization_id']);

        foreach ($users as $user) {
            // Check if organization exists
            $orgExists = DB::table('organizations')->where('id', $user->organization_id)->exists();

            if ($orgExists) {
                // Insert into pivot table
                DB::table('organization_user')->insertOrIgnore([
                    'organization_id' => $user->organization_id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Clear pivot table data that was migrated
        DB::table('organization_user')->truncate();
    }
};
