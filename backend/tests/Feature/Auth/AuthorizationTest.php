<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected_on_protected_route(): void
    {
        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'unauthenticated');
        $response->assertJsonPath('error.status', 401);
    }

    public function test_non_admin_is_rejected_by_policy_on_admin_only_action(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $response = $this->actingAs($employee)->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'forbidden');
        $response->assertJsonPath('error.status', 403);
    }

    public function test_admin_can_access_admin_only_action(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/users');

        $response->assertOk();
    }
}
