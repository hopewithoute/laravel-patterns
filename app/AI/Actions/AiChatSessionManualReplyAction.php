<?php

namespace App\AI\Actions;

use App\AI\Agents\WorkspaceAssistantAgent;
use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Contracts\PostRunHook;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\Models\AiChatSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

readonly class AiChatSessionManualReplyAction
{
    public function __construct(
        private BuildRuntimeRunReportAction $buildRuntimeRunReportAction,
        private PostRunHook $postRunHook,
    ) {}

    /**
     * @param  array<int, ArtifactPayload>  $artifacts
     */
    public function execute(
        AiChatSession $session,
        User $user,
        PreparedWorkspaceAssistantRun $preparedRun,
        string $assistantText,
        array $artifacts = [],
    ): AiChatSession {
        $conversationId = $session->conversation_id ?: (string) Str::uuid();
        $title = Str::limit($preparedRun->context->prompt, 80, preserveWords: true);
        $userMessageId = (string) Str::uuid();
        $assistantMessageId = (string) Str::uuid();
        $report = $this->buildRuntimeRunReportAction->fromManualReply(
            conversationId: $conversationId,
            assistantMessageId: $assistantMessageId,
            preparedRun: $preparedRun,
            artifacts: $artifacts,
        );

        DB::transaction(function () use ($artifacts, $assistantMessageId, $assistantText, $conversationId, $preparedRun, $report, $session, $title, $user, $userMessageId): void {
            $conversationExists = DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->exists();

            if (! $conversationExists) {
                DB::table('agent_conversations')->insert([
                    'id' => $conversationId,
                    'user_id' => $user->id,
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('agent_conversation_messages')->insert([
                [
                    'id' => $userMessageId,
                    'conversation_id' => $conversationId,
                    'user_id' => $user->id,
                    'agent' => class_basename(WorkspaceAssistantAgent::class),
                    'role' => 'user',
                    'content' => $preparedRun->context->prompt,
                    'attachments' => json_encode([], JSON_THROW_ON_ERROR),
                    'tool_calls' => json_encode([], JSON_THROW_ON_ERROR),
                    'tool_results' => json_encode([], JSON_THROW_ON_ERROR),
                    'usage' => json_encode([], JSON_THROW_ON_ERROR),
                    'meta' => json_encode([], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => $assistantMessageId,
                    'conversation_id' => $conversationId,
                    'user_id' => $user->id,
                    'agent' => class_basename(WorkspaceAssistantAgent::class),
                    'role' => 'assistant',
                    'content' => $assistantText,
                    'attachments' => json_encode([], JSON_THROW_ON_ERROR),
                    'tool_calls' => json_encode([], JSON_THROW_ON_ERROR),
                    'tool_results' => json_encode([], JSON_THROW_ON_ERROR),
                    'usage' => json_encode([], JSON_THROW_ON_ERROR),
                    'meta' => json_encode([
                        ...$report->toMessageMeta(),
                        'artifacts' => array_map(
                            fn (ArtifactPayload $artifact): array => $artifact->toArray(),
                            $artifacts,
                        ),
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $session->forceFill([
                'conversation_id' => $conversationId,
                'title' => $title,
                'last_message_at' => now(),
            ])->save();
        });

        $this->postRunHook->handle($report);

        return $session->refresh();
    }
}
