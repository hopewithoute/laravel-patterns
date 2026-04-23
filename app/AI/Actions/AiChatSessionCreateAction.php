<?php

namespace App\AI\Actions;

use App\Models\AiChatSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AiChatSessionCreateAction
{
    public function __construct(
        private AiChatSession $model,
    ) {}

    public function execute(User $user, string $organizationId): AiChatSession
    {
        return DB::transaction(function () use ($user, $organizationId) {
            return $this->model->create([
                'organization_id' => $organizationId,
                'user_id' => $user->id,
                'conversation_id' => null,
                'title' => 'New chat',
                'last_message_at' => null,
            ]);
        });
    }
}
