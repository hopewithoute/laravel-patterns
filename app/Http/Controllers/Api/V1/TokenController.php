<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\CreateTokenData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->latest()->get();

        return response()->json(['data' => $tokens]);
    }

    public function store(CreateTokenData $data, Request $request): JsonResponse
    {
        $token = $request->user()->createToken(
            $data->name,
            $data->abilities ?? ['*'],
            $data->expires_at
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $data->name,
        ], 201);
    }

    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->find($tokenId);

        if (! $token) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        if (method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json(['message' => 'Token revoked.']);
    }
}
