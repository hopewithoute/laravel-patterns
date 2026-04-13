<?php

namespace App\Actions;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Action to regenerate organization invite code.
 */
readonly class OrganizationRegenerateInviteCodeAction
{
    public function execute(Organization $organization): string
    {
        return DB::transaction(function () use ($organization) {
            $code = strtoupper(Str::random(8));
            $organization->update([
                'invite_code' => $code,
            ]);

            return $code;
        });
    }
}
