<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createApiUser(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        return [$user, $token];
    }

    private function headers(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_user_can_list_their_tokens(): void
    {
        [$user, $token] = $this->createApiUser();

        // Create additional tokens
        $user->createToken('second-token');
        $user->createToken('third-token');

        $response = $this->getJson('/api/auth/tokens', $this->headers($token));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_token(): void
    {
        [$user, $token] = $this->createApiUser();

        $response = $this->postJson('/api/auth/tokens', [
            'name' => 'mobile-app',
        ], $this->headers($token));

        $response->assertCreated()
            ->assertJsonStructure(['token', 'name'])
            ->assertJsonPath('name', 'mobile-app');

        // Token should be usable
        $newToken = $response->json('token');
        $this->getJson('/api/auth/me', $this->headers($newToken))->assertOk();
    }

    public function test_user_can_create_token_with_abilities(): void
    {
        [$user, $token] = $this->createApiUser();

        $response = $this->postJson('/api/auth/tokens', [
            'name' => 'read-only',
            'abilities' => ['tasks:read', 'projects:read'],
        ], $this->headers($token));

        $response->assertCreated();

        // Verify token has correct abilities
        $newToken = $user->tokens()->where('name', 'read-only')->first();
        $this->assertEquals(['tasks:read', 'projects:read'], $newToken->abilities);
    }

    public function test_user_can_create_token_with_expiry(): void
    {
        [$user, $token] = $this->createApiUser();

        $expiresAt = now()->addDays(30)->toIso8601String();

        $response = $this->postJson('/api/auth/tokens', [
            'name' => 'expiring-token',
            'expires_at' => $expiresAt,
        ], $this->headers($token));

        $response->assertCreated();

        $newToken = $user->tokens()->where('name', 'expiring-token')->first();
        $this->assertNotNull($newToken->expires_at);
    }

    public function test_user_can_revoke_token(): void
    {
        [$user, $token] = $this->createApiUser();

        $tokenToRevoke = $user->createToken('revoke-me');

        $response = $this->deleteJson(
            "/api/auth/tokens/{$tokenToRevoke->accessToken->id}",
            [],
            $this->headers($token)
        );

        $response->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenToRevoke->accessToken->id,
        ]);
    }

    public function test_user_cannot_revoke_other_users_token(): void
    {
        [$user1, $token1] = $this->createApiUser();
        [$user2] = $this->createApiUser();

        $user2Token = $user2->createToken('other-token');

        $response = $this->deleteJson(
            "/api/auth/tokens/{$user2Token->accessToken->id}",
            [],
            $this->headers($token1)
        );

        // Returns 404 because token doesn't belong to user1
        $response->assertNotFound();
    }

    public function test_user_can_logout_and_revoke_current_token(): void
    {
        [$user, $token] = $this->createApiUser();

        // Verify token exists before logout
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Logout using the bearer token
        $this->postJson('/api/auth/logout', [], $this->headers($token))
            ->assertOk();

        // Token should be deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_cannot_access_tokens_without_auth(): void
    {
        $this->getJson('/api/auth/tokens')->assertUnauthorized();
    }
}
