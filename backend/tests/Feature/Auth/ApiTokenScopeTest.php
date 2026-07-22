<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoped_token_is_accepted_on_its_permitted_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('ecommerce-api', ['products:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/public/products');

        $response->assertOk();
    }

    public function test_token_is_rejected_outside_its_granted_scope(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('other-integration', ['orders:write'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/public/products');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'invalid_scope');
        $response->assertJsonPath('error.status', 403);
    }
}
