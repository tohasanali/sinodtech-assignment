<?php

namespace Tests\Feature\Crm;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeCustomerSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function employee(): User
    {
        return User::factory()->create(['role' => UserRole::Employee]);
    }

    public function test_employee_only_sees_their_own_assigned_customers(): void
    {
        $employee = $this->employee();
        $otherEmployee = $this->employee();

        $mine = Customer::factory()->create(['employee_id' => $employee->id]);
        Customer::factory()->create(['employee_id' => $otherEmployee->id]);
        Customer::factory()->create(['employee_id' => null]);

        $response = $this->actingAs($employee)->getJson('/api/v1/employee/customers');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_lost_only_filter_narrows_to_lost_assigned_customers(): void
    {
        $employee = $this->employee();

        $lostCustomer = Customer::factory()->create(['employee_id' => $employee->id]);
        Sale::factory()->create(['customer_id' => $lostCustomer->id, 'created_at' => now()->subDays(120)]);

        $activeCustomer = Customer::factory()->create(['employee_id' => $employee->id]);
        Sale::factory()->create(['customer_id' => $activeCustomer->id, 'created_at' => now()->subDays(5)]);

        $response = $this->actingAs($employee)->getJson('/api/v1/employee/customers?lost_only=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $lostCustomer->id);

        $response = $this->actingAs($employee)->getJson('/api/v1/employee/customers');
        $response->assertJsonCount(2, 'data');
    }

    public function test_assigned_employee_can_reengage_their_own_customer(): void
    {
        $employee = $this->employee();
        $customer = Customer::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($employee)->postJson("/api/v1/admin/customers/{$customer->id}/reengage");

        $response->assertOk();
        $this->assertNotNull($customer->fresh()->last_contacted_at);
    }

    public function test_employee_is_forbidden_from_reengaging_another_employees_customer(): void
    {
        $employee = $this->employee();
        $otherEmployee = $this->employee();
        $customer = Customer::factory()->create(['employee_id' => $otherEmployee->id]);

        $response = $this->actingAs($employee)->postJson("/api/v1/admin/customers/{$customer->id}/reengage");

        $response->assertStatus(403);
    }

    public function test_admin_can_reengage_any_customer_regardless_of_assignment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = $this->employee();
        $customer = Customer::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/customers/{$customer->id}/reengage");

        $response->assertOk();
    }
}
