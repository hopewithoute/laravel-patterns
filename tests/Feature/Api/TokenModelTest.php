<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_has_formatted_accessors(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['tasks:read', 'projects:read']);

        $accessToken = $token->accessToken;

        // Test is_expired accessor
        $this->assertFalse($accessToken->is_expired);

        // Test formatted_created_at accessor
        $this->assertIsString($accessToken->formatted_created_at);
    }

    public function test_expired_token_returns_correct_is_expired(): void
    {
        $user = User::factory()->create();
        $user->createToken('expired-token', ['*'], now()->subDay());

        $expiredToken = $user->tokens()->first();
        $this->assertTrue($expiredToken->is_expired);
    }

    public function test_token_without_expiry_is_not_expired(): void
    {
        $user = User::factory()->create();
        $user->createToken('no-expiry');

        $token = $user->tokens()->first();
        $this->assertFalse($token->is_expired);
    }

    public function test_token_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $user->createToken('test-token');

        $token = $user->tokens()->first();
        $this->assertInstanceOf(User::class, $token->tokenable);
        $this->assertEquals($user->id, $token->tokenable->id);
    }

    public function test_token_has_abilities_array(): void
    {
        $user = User::factory()->create();
        $user->createToken('scoped', ['tasks:read', 'projects:read']);

        $token = $user->tokens()->first();
        $this->assertEquals(['tasks:read', 'projects:read'], $token->abilities);
    }
}
