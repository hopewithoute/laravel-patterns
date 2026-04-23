<?php

namespace App\Http\Controllers;

use App\AI\Actions\AiChatSessionCreateAction;
use App\Http\Resources\AiChatSessionResource;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AiChatSessionController extends Controller
{
    public function store(Request $request, AiChatSessionCreateAction $createAction): JsonResponse
    {
        $organization = GetActiveOrganization::resolveOrFail();
        $session = $createAction->execute($request->user(), $organization->id);

        return response()->json([
            'session' => new AiChatSessionResource($session),
        ], Response::HTTP_CREATED);
    }
}
