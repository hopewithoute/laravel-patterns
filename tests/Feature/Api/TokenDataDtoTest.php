<?php

namespace Tests\Feature\Api;

use App\Data\TokenData;
use Tests\TestCase;

class TokenDataDtoTest extends TestCase
{
    public function test_token_data_creation(): void
    {
        $data = new TokenData(
            name: 'mobile-app',
            abilities: ['tasks:read', 'projects:read'],
            expiresAt: now()->addDays(30),
        );

        $this->assertEquals('mobile-app', $data->name);
        $this->assertEquals(['tasks:read', 'projects:read'], $data->abilities);
        $this->assertNotNull($data->expiresAt);
    }

    public function test_token_data_with_defaults(): void
    {
        $data = new TokenData(
            name: 'default-token',
        );

        $this->assertEquals('default-token', $data->name);
        $this->assertEquals(['*'], $data->abilities);
        $this->assertNull($data->expiresAt);
    }

    public function test_token_data_from_request(): void
    {
        $data = TokenData::from([
            'name' => 'api-token',
            'abilities' => ['read', 'write'],
            'expires_at' => '2026-12-31',
        ]);

        $this->assertEquals('api-token', $data->name);
        $this->assertEquals(['read', 'write'], $data->abilities);
        $this->assertNotNull($data->expiresAt);
    }
}
