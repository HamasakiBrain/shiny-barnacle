<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_and_revoked_tokens_are_denied(): void
    {
        $u = User::factory()->create();
        $expired = $u->createToken('expired', ['*'], now()->subMinute())->plainTextToken;
        $this->withToken($expired)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $token = $u->createToken('valid')->plainTextToken;
        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0;$i < 5;$i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'absent@example.test',
                'password' => 'bad',
            ])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'absent@example.test',
            'password' => 'bad',
        ])
            ->assertStatus(429);
    }
}
