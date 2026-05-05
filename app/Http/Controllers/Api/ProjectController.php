<?php

namespace App\Http\Controllers\Api;

use App\Actions\ProjectCreateAction;
use App\Actions\ProjectUpdateAction;
use App\Data\ProjectData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $organizationId = $request->header('X-Organization');

        $projects = Project::where('organization_id', $organizationId)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ProjectResource::collection($projects);
    }

    public function store(Request $request, ProjectCreateAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $validated['organization_id'] = $request->header('X-Organization');

        $project = $action->execute(ProjectData::from($validated));

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        return ProjectResource::make($project);
    }

    public function update(Request $request, Project $project, ProjectUpdateAction $action): ProjectResource
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // Merge existing project data with validated input
        $data = array_merge($project->toArray(), $validated);
        $data['organization_id'] = $project->organization_id;

        $project = $action->execute(ProjectData::from($data), $project);

        return ProjectResource::make($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(null, 204);
    }
}
