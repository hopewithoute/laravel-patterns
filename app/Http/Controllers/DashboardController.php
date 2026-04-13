<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Supports\GetActiveOrganization;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(DashboardService $dashboardService): Response
    {
        $organizationId = GetActiveOrganization::getSelected();

        $statistics = $organizationId
            ? $dashboardService->getStatistics($organizationId)
            : [
                'projects' => ['total' => 0, 'active' => 0],
                'tasks' => ['total' => 0, 'todo' => 0, 'in_progress' => 0, 'review' => 0, 'done' => 0, 'overdue' => 0],
                'recent_tasks' => [],
                'upcoming_deadlines' => [],
            ];

        return Inertia::render('Dashboard/Index', $statistics);
    }
}
