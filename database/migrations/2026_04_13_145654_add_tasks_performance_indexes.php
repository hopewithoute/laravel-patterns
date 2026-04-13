<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for tasks table.
 *
 * Access patterns:
 * - Kanban board: WHERE organization_id = ? AND due_date BETWEEN ? AND ?
 * - Recent tasks: WHERE organization_id = ? ORDER BY created_at DESC
 * - Upcoming deadlines: WHERE organization_id = ? AND due_date BETWEEN ? AND ? AND status != 'Done'
 * - Overdue: WHERE organization_id = ? AND due_date < ? AND status != 'Done'
 * - Project task counts: WHERE project_id = ? AND status = ?
 * - Default sort: ORDER BY priority DESC, due_date ASC
 * - Kanban sort: ORDER BY sort_order ASC, priority DESC, title ASC
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Kanban board queries - organization + date range filtering
            $table->index(['organization_id', 'due_date'], 'tasks_org_due_date_idx');

            // Recent tasks - organization + created_at for latest()
            $table->index(['organization_id', 'created_at'], 'tasks_org_created_at_idx');

            // Upcoming deadlines & overdue queries - org + status + due_date
            $table->index(['organization_id', 'status', 'due_date'], 'tasks_org_status_due_idx');

            // Project task counts - filter by project and status
            $table->index(['project_id', 'status'], 'tasks_project_status_idx');

            // Kanban ordering - sort_order is primary sort in kanban
            $table->index(['organization_id', 'sort_order'], 'tasks_org_sort_order_idx');

            // Priority filtering + sorting
            $table->index(['organization_id', 'priority'], 'tasks_org_priority_idx');

            // Combined filter for assigned tasks with due date
            $table->index(['assigned_to', 'due_date'], 'tasks_assignee_due_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_org_due_date_idx');
            $table->dropIndex('tasks_org_created_at_idx');
            $table->dropIndex('tasks_org_status_due_idx');
            $table->dropIndex('tasks_project_status_idx');
            $table->dropIndex('tasks_org_sort_order_idx');
            $table->dropIndex('tasks_org_priority_idx');
            $table->dropIndex('tasks_assignee_due_date_idx');
        });
    }
};