<?php

namespace App\Actions;

use App\Data\ProjectData;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Action to update an existing project.
 */
readonly class ProjectUpdateAction
{
    public function execute(ProjectData $data, Project $project): Project
    {
        return DB::transaction(function () use ($data, $project) {
            $project->update([
                'name' => $data->name,
                'description' => $data->description,
                'color' => $data->color,
                'is_active' => $data->is_active,
            ]);

            return $project->fresh();
        });
    }
}
