<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->latest()->get();

        return response()->json(['data' => $tokens]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $expiresAt = isset($validated['expires_at'])
            ? Carbon::parse($validated['expires_at'])
            : null;

        $token = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'] ?? ['*'],
            $expiresAt
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $validated['name'],
        ], 201);
    }

    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->find($tokenId);

        if (! $token) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        $token->delete();

        return response()->json(['message' => 'Token revoked.']);
    }
}
