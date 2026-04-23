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
            return $this->model->create($data->toModelData());
        });
    }
}
