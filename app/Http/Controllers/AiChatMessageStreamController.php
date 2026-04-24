<?php

namespace App\Http\Controllers;

use App\AI\Actions\AiChatSessionManualReplyAction;
use App\AI\Actions\AiChatSessionSyncAction;
use App\AI\Actions\BuildAiArtifactsAction;
use App\AI\Actions\StreamAiChatAction;
use App\AI\Agents\WorkspaceAssistantAgent;
use App\AI\Data\AiChatPromptData;
use App\AI\Resolvers\AiModelResolver;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\WorkspaceAssistantRuntime;
use App\Models\AiChatSession;
use App\Models\Organization;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\Request;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Symfony\Component\HttpFoundation\Response;

class AiChatMessageStreamController extends Controller
{
    public function __construct(
        private readonly AiModelResolver $modelResolver,
    ) {}

    public function __invoke(
        AiChatPromptData $data,
        Request $request,
        AiChatSession $aiChatSession,
        AiChatSessionSyncAction $syncAction,
        AiChatSessionManualReplyAction $manualReplyAction,
        BuildAiArtifactsAction $buildAiArtifactsAction,
        WorkspaceAssistantRuntime $workspaceAssistantRuntime,
    ): Response {
        $organization = GetActiveOrganization::resolveOrFail();
        $user = $request->user();
        $artifactIntent = ArtifactIntent::tryFrom($data->artifact_mode) ?? ArtifactIntent::Auto;
        $session = $this->resolveSession($aiChatSession, $organization, $user->id);
        $preparedRun = $workspaceAssistantRuntime->prepare(
            user: $user,
            organization: $organization,
            session: $session,
            prompt: $data->prompt,
            requestedArtifactMode: $artifactIntent,
            debug: $request->boolean('debug'),
            provider: config('ai.default'),
            model: $this->modelResolver->resolve(),
        );

        if (! $preparedRun->decision->isAllowed()) {
            return $this->streamRejectedRun($manualReplyAction, $session, $user, $preparedRun);
        }

        $agent = WorkspaceAssistantAgent::make(
            user: $user,
            organization: $organization,
            instructions: $preparedRun->instructions,
            tools: $preparedRun->tools,
        );

        $this->prepareAgentConversation($agent, $session, $user);

        $stream = $agent
            ->stream(
                $data->prompt,
                provider: $preparedRun->context->provider,
                model: $preparedRun->context->model,
            )
            ->then(function (StreamedAgentResponse $response) use ($preparedRun, $session, $syncAction): void {
                $syncAction->execute($session, $preparedRun, $response);
            });

        return app(StreamAiChatAction::class)->streamAgentResponse($preparedRun, $stream);
    }

    private function resolveSession(AiChatSession $aiChatSession, Organization $organization, string $userId): AiChatSession
    {
        return AiChatSession::query()
            ->whereKey($aiChatSession->id)
            ->where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    private function prepareAgentConversation(WorkspaceAssistantAgent $agent, AiChatSession $session, User $user): void
    {
        if ($session->conversation_id !== null) {
            $agent->continue($session->conversation_id, as: $user);

            return;
        }

        $agent->forUser($user);
    }

    private function streamRejectedRun(
        AiChatSessionManualReplyAction $manualReplyAction,
        AiChatSession $session,
        User $user,
        PreparedWorkspaceAssistantRun $preparedRun,
    ): Response {
        $assistantText = $preparedRun->decision->rejectionMessage();

        $manualReplyAction->execute(
            $session,
            $user,
            $preparedRun,
            $assistantText,
        );

        return app(StreamAiChatAction::class)->streamManualResponse(
            $assistantText,
            (string) ($preparedRun->context->provider ?? config('ai.default')),
            $preparedRun->context->model,
        );
    }
}
