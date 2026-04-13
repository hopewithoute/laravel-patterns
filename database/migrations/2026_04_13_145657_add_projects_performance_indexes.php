<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for projects table.
 *
 * Access patterns:
 * - Project aggregation by org: WHERE organization_id = ?
 *
 * Note: Index for (organization_id, is_active) already exists in original migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Single column index for simple org lookups
            // (complement to existing composite index on organization_id, is_active)
            $table->index('organization_id', 'projects_org_idx');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_org_idx');
        });
    }
};