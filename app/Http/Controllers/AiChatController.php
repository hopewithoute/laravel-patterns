<?php

namespace App\Http\Controllers;

use App\AI\Runtime\Artifacts\RuntimeArtifactModeCatalog;
use App\AI\Runtime\Contracts\AvailableToolResolver;
use App\Http\Resources\AiChatSessionResource;
use App\Models\AiChatSession;
use App\Supports\GetActiveOrganization;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AiChatController extends Controller
{
    public function index(
        Request $request,
        AvailableToolResolver $availableToolResolver,
        RuntimeArtifactModeCatalog $runtimeArtifactModeCatalog,
    ): Response {
        $organization = GetActiveOrganization::resolveOrFail();
        $user = $request->user();
        $sessionId = $request->string('session')->toString();
        $sessions = $this->sessionsFor($organization->id, $user->id);
        $activeSession = $this->resolveActiveSession($sessions, $sessionId);

        return Inertia::render('Ai/Index', [
            'sessions' => AiChatSessionResource::collection($sessions)->resolve(),
            'activeSessionId' => $activeSession?->id,
            'messages' => $activeSession ? $this->loadMessages($activeSession) : [],
            'workspace' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'role' => $organization->getMemberRole($user),
            'availableTools' => $availableToolResolver->uiIdentifiers(),
            'availableArtifactModes' => $runtimeArtifactModeCatalog->options(),
        ]);
    }

    /**
     * @return EloquentCollection<int, AiChatSession>
     */
    private function sessionsFor(string $organizationId, string $userId): EloquentCollection
    {
        return AiChatSession::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->orderByRaw('coalesce(last_message_at, updated_at) desc')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }

    private function resolveActiveSession(EloquentCollection $sessions, string $sessionId): ?AiChatSession
    {
        if ($sessionId === '') {
            return $sessions->first();
        }

        return $sessions->firstWhere('id', $sessionId)
            ?? abort(404, 'AI chat session not found.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadMessages(AiChatSession $session): array
    {
        if ($session->conversation_id === null) {
            return [];
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $session->conversation_id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (object $message): array => $this->serializeMessage($message))
            ->all();
    }

    /**
     * @return array<string|int, mixed>
     */
    private function decodeJson(string $payload): array
    {
        return json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeJsonList(string $payload): array
    {
        $decoded = $this->decodeJson($payload);

        if (array_is_list($decoded)) {
            return $decoded;
        }

        return $decoded === [] ? [] : [$decoded];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonMap(string $payload): array
    {
        $decoded = $this->decodeJson($payload);

        return array_is_list($decoded) ? [] : $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(object $message): array
    {
        $meta = $this->decodeJson($message->meta);

        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'artifacts' => is_array($meta['artifacts'] ?? null) ? $meta['artifacts'] : [],
            'tool_calls' => $this->decodeJsonList($message->tool_calls),
            'tool_results' => $this->decodeJsonList($message->tool_results),
            'usage' => $this->decodeJsonMap($message->usage),
            'meta' => $this->decodeJsonMap($message->meta),
            'created_at' => $message->created_at,
        ];
    }
}
