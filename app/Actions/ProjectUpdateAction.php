<?php

namespace App\Actions;

use App\Data\ProjectData;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Action to update an existing project.
 */
class ProjectUpdateAction
{
    public function execute(ProjectData $data, Project $project): Project
    {
        return DB::transaction(function () use ($data, $project) {
            $project->update($data->toModelData());

            return $project->fresh();
        });
    }
}
