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

    public function store(ProjectData $data, ProjectCreateAction $action): JsonResponse
    {
        $project = $action->execute($data);

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        return ProjectResource::make($project);
    }

    public function update(ProjectData $data, Project $project, ProjectUpdateAction $action): ProjectResource
    {
        $project = $action->execute($data, $project);

        return ProjectResource::make($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(null, 204);
    }
}
