<?php

namespace App\Actions;

use App\Data\WorkspaceSetData;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Validation\ValidationException;

/**
 * Action to set the active workspace for a user.
 */
readonly class WorkspaceSetAction
{
    public function execute(WorkspaceSetData $data, User $user): void
    {
        // Validate membership
        if (! $user->belongsToOrganization($data->organization_id)) {
            throw ValidationException::withMessages([
                'organization_id' => 'You do not have access to this organization.',
            ]);
        }

        GetActiveOrganization::setWithoutValidation($data->organization_id);
    }
}
