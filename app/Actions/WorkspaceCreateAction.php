<?php

namespace App\Actions;

use App\Data\WorkspaceCreateData;
use App\Models\Organization;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new workspace and invite initial members.
 */
readonly class WorkspaceCreateAction
{
    /**
     * @return array{organization: Organization, invited_count: int}
     */
    public function execute(WorkspaceCreateData $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user) {
            $organization = Organization::create([
                'name' => $data->name,
                'description' => $data->description,
                'is_active' => true,
            ]);

            // Add user as admin
            $organization->addMember($user, 'admin');

            // Process invite emails
            $invitedCount = 0;
            if (! empty($data->invite_emails)) {
                $emails = preg_split('/[,\n]/', $data->invite_emails);
                $emails = array_map('trim', $emails);
                $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

                foreach ($emails as $email) {
                    $invitedUser = User::where('email', $email)->first();
                    if ($invitedUser && ! $organization->hasMember($invitedUser)) {
                        $organization->addMember($invitedUser, 'member');
                        $invitedCount++;
                    }
                }
            }

            // Set as active workspace
            GetActiveOrganization::setWithoutValidation($organization->id);

            return [
                'organization' => $organization,
                'invited_count' => $invitedCount,
            ];
        });
    }
}
