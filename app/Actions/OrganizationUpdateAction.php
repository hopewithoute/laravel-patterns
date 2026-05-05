<?php

namespace App\Actions;

use App\Data\OrganizationData;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Action to update an existing organization.
 */
class OrganizationUpdateAction
{
    public function execute(OrganizationData $data, Organization $organization): Organization
    {
        return DB::transaction(function () use ($data, $organization) {
            $organization->update($data->toModelData());

            return $organization->fresh();
        });
    }
}
