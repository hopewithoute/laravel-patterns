<?php

namespace App\Actions;

use App\Data\ProjectData;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new project.
 */
readonly class ProjectCreateAction
{
    public function __construct(
        private readonly Project $model,
    ) {}

    public function execute(ProjectData $data): Project
    {
        return DB::transaction(function () use ($data) {
            return $this->model->create([
                'organization_id' => $data->organization_id ?? session('organization_id'),
                'name' => $data->name,
                'description' => $data->description,
                'color' => $data->color ?? '#3B82F6', // Default blue
                'is_active' => $data->is_active,
            ]);
        });
    }
}
