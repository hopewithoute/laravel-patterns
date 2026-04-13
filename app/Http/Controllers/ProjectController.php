<?php

namespace App\Http\Controllers;

use App\Actions\ProjectCreateAction;
use App\Actions\ProjectDeleteAction;
use App\Actions\ProjectUpdateAction;
use App\Data\ProjectData;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\QueryBuilders\ProjectIndexQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(ProjectIndexQuery $query): Response
    {
        return Inertia::render('Project/Index', [
            'projects' => $query->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): Response
    {
        return Inertia::render('Project/Form');
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectData $data, ProjectCreateAction $action): RedirectResponse
    {
        $project = $action->execute($data);

        return redirect()
            ->route('projects.show', $project)
            ->with('message', [
                'type' => 'success',
                'text' => 'Project created successfully.',
            ]);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): Response
    {
        $project->loadCount([
            'tasks as done_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Done),
            'tasks as in_progress_tasks_count' => fn ($q) => $q->where('status', TaskStatus::InProgress),
            'tasks as todo_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Todo),
        ]);

        return Inertia::render('Project/Show', [
            'project' => $project,
            'tasks' => $project->tasks()->with('assignee')->latest()->paginate(10),
        ]);
    }

    /**
     * Show the form for editing the project.
     */
    public function edit(Project $project): Response
    {
        return Inertia::render('Project/Form', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(
        ProjectData $data,
        Project $project,
        ProjectUpdateAction $action
    ): RedirectResponse {
        $action->execute($data, $project);

        return redirect()
            ->route('projects.show', $project)
            ->with('message', [
                'type' => 'success',
                'text' => 'Project updated successfully.',
            ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project, ProjectDeleteAction $action): RedirectResponse
    {
        $action->execute($project);

        return redirect()
            ->route('projects.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Project deleted successfully.',
            ]);
    }
}
