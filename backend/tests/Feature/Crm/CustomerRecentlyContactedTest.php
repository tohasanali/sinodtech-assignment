<?php

namespace Tests\Feature\Crm;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRecentlyContactedTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_never_contacted_is_not_recently_contacted(): void
    {
        $customer = Customer::factory()->create(['last_contacted_at' => null]);

        $this->assertFalse($customer->wasRecentlyContacted());
    }

    public function test_customer_contacted_just_now_is_recently_contacted(): void
    {
        $customer = Customer::factory()->create(['last_contacted_at' => now()]);

        $this->assertTrue($customer->wasRecentlyContacted());
    }

    public function test_customer_contacted_beyond_the_cooldown_is_not_recently_contacted(): void
    {
        $cooldownDays = config('crm.recontact_cooldown_days');
        $customer = Customer::factory()->create(['last_contacted_at' => now()->subDays($cooldownDays + 1)]);

        $this->assertFalse($customer->wasRecentlyContacted());
    }

    public function test_employee_customers_endpoint_exposes_recently_contacted(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create(['employee_id' => $employee->id, 'last_contacted_at' => now()]);

        $response = $this->actingAs($employee)->getJson('/api/v1/employee/customers');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $customer->id);
        $response->assertJsonPath('data.0.recently_contacted', true);
    }
}
