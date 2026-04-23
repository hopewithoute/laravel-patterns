<?php

namespace App\AI\Actions;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Contracts\PostRunHook;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\Tools\ToolExecutionResult;
use App\Models\AiChatSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\AgentResponse;

readonly class AiChatSessionSyncAction
{
    public function __construct(
        private BuildAiArtifactsAction $buildAiArtifactsAction,
        private BuildRuntimeRunReportAction $buildRuntimeRunReportAction,
        private PostRunHook $postRunHook,
    ) {}

    public function execute(
        AiChatSession $session,
        PreparedWorkspaceAssistantRun $preparedRun,
        AgentResponse $response,
    ): AiChatSession {
        $conversationId = $response->conversationId ?? $session->conversation_id;
        $storedTitle = null;

        if ($conversationId !== null) {
            $storedTitle = DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->value('title');
        }

        return DB::transaction(function () use ($session, $conversationId, $storedTitle, $preparedRun, $response) {
            $session->forceFill([
                'conversation_id' => $conversationId,
                'title' => $storedTitle ?: Str::limit($preparedRun->context->prompt, 80, preserveWords: true),
                'last_message_at' => now(),
            ])->save();

            if ($conversationId !== null) {
                $this->syncArtifacts($conversationId, $preparedRun, $response->toolResults ?? new Collection, $response->text, $response);
            }

            return $session->refresh();
        });
    }

    /**
     * @param  iterable<mixed>  $toolResults
     */
    private function syncArtifacts(
        string $conversationId,
        PreparedWorkspaceAssistantRun $preparedRun,
        iterable $toolResults,
        string $assistantText,
        AgentResponse $response,
    ): void {
        $runtimeToolResults = $preparedRun->toolExecutionJournal?->hasRecordedResults()
            ? $preparedRun->toolExecutionJournal->all()
            : [];
        $artifactPayloads = $this->buildAiArtifactsAction->resolve(
            $preparedRun,
            $preparedRun->toolExecutionJournal?->hasRecordedResults() ? $runtimeToolResults : $toolResults,
            $assistantText
        );
        $artifacts = array_map(
            fn (ArtifactPayload $artifact): array => $artifact->toArray(),
            $artifactPayloads,
        );
        $assistantMessage = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->first(['id', 'meta']);

        if ($assistantMessage === null) {
            return;
        }

        $meta = json_decode($assistantMessage->meta ?? '[]', true);

        if (! is_array($meta)) {
            $meta = [];
        }

        $typedRuntimeToolResults = array_values(array_filter(
            $runtimeToolResults,
            fn (mixed $result): bool => $result instanceof ToolExecutionResult,
        ));
        $report = $this->buildRuntimeRunReportAction->fromAgentResponse(
            conversationId: $conversationId,
            assistantMessageId: $assistantMessage->id,
            preparedRun: $preparedRun,
            toolResults: $typedRuntimeToolResults,
            artifacts: $artifactPayloads,
            response: $response,
        );
        $meta = [
            ...$meta,
            ...$report->toMessageMeta(),
            'artifacts' => $artifacts,
        ];

        DB::table('agent_conversation_messages')
            ->where('id', $assistantMessage->id)
            ->update([
                'meta' => json_encode($meta, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        $this->postRunHook->handle($report);
    }
}
