<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ProjectCreateAction;
use App\Actions\ProjectUpdateAction;
use App\Data\ProjectData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProjectResource;
use App\Models\Project;
use App\QueryBuilders\ProjectIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(ProjectIndexQuery $query): AnonymousResourceCollection
    {
        return ProjectResource::collection($query->jsonPaginate());
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
