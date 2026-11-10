<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_cannot_escalate_role_and_token_is_revoked(): void
    {
        $r = $this->postJson(
            '/api/v1/auth/register',
            [
                'name' => 'Customer',
                'email' => 'new@example.test',
                'password' => 'Long-test-password',
                'password_confirmation' => 'Long-test-password',
                'role' => 'admin',
            ],
        );
        $r->assertCreated()->assertJsonPath('data.user.role', 'customer');
        $token = $r->json('data.token');
        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_and_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.test',
            'password' => 'Long-test-password',
        ]);
        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.test',
            'password' => 'incorrect',
        ])
            ->assertUnprocessable();
        $this->postJson(
            '/api/v1/auth/login',
            [
                'email' => 'login@example.test',
                'password' => 'Long-test-password',
            ],
        )
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_anonymous_and_customer_cannot_administer_catalog(): void
    {
        $this->postJson('/api/v1/admin/products', [])->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/admin/products', [])
            ->assertForbidden();
    }

    public function test_registration_rejects_weak_password(): void
    {
        $this->postJson(
            '/api/v1/auth/register',
            [
                'name' => 'Test',
                'email' => 'test@example.test',
                'password' => '123',
                'password_confirmation' => '123',
            ],
        )
            ->assertUnprocessable();
    }
}
