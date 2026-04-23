<?php

namespace App\Actions;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Action to delete a project.
 */
class ProjectDeleteAction
{
    public function execute(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project->delete();
        });
    }
}
