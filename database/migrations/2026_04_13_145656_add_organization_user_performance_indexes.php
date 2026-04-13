<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for organization_user pivot table.
 *
 * Access patterns:
 * - User's organizations: WHERE user_id = ?
 * - Permission checks: WHERE user_id = ? AND role = ?
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            // Lookup user's organizations (reverse of composite primary)
            $table->index('user_id', 'organization_user_user_idx');

            // Filter by user + role for permission checks
            $table->index(['user_id', 'role'], 'organization_user_user_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropIndex('organization_user_user_idx');
            $table->dropIndex('organization_user_user_role_idx');
        });
    }
};