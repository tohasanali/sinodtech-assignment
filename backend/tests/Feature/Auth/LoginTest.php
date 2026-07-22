<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        $response = $this->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->postJson('/api/v1/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ]);

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        $response = $this->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->postJson('/api/v1/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
        $response->assertJsonPath('error.status', 422);
        $response->assertJsonPath('error.errors.email.0', 'The provided credentials are incorrect.');
        $this->assertGuest();
    }
}
