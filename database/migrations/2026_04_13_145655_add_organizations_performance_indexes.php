<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for organizations table.
 *
 * Access patterns:
 * - Invite code lookup: WHERE invite_code = ?
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Invite code lookup for registration
            $table->index('invite_code', 'organizations_invite_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('organizations_invite_code_idx');
        });
    }
};