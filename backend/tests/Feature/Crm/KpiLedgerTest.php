<?php

namespace Tests\Feature\Crm;

use App\Enums\UserRole;
use App\Events\SaleCreated;
use App\Listeners\IncrementEmployeeKpi;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EmployeeKpi;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function sale(?Customer $customer): Sale
    {
        return Sale::factory()->create([
            'branch_id' => Branch::factory(),
            'customer_id' => $customer?->id,
        ]);
    }

    public function test_kpi_row_created_when_assigned_employee_and_was_lost_true(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create(['employee_id' => $employee->id]);
        $sale = $this->sale($customer);

        (new IncrementEmployeeKpi)->handle(new SaleCreated($sale, wasLost: true));

        $this->assertSame(1, EmployeeKpi::count());
        $this->assertDatabaseHas('employee_kpis', [
            'user_id' => $employee->id,
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'points' => config('crm.reactivation_points'),
        ]);
    }

    public function test_no_kpi_row_when_assigned_but_not_lost(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create(['employee_id' => $employee->id]);
        $sale = $this->sale($customer);

        (new IncrementEmployeeKpi)->handle(new SaleCreated($sale, wasLost: false));

        $this->assertSame(0, EmployeeKpi::count());
    }

    public function test_no_kpi_row_when_lost_but_unassigned(): void
    {
        $customer = Customer::factory()->create(['employee_id' => null]);
        $sale = $this->sale($customer);

        (new IncrementEmployeeKpi)->handle(new SaleCreated($sale, wasLost: true));

        $this->assertSame(0, EmployeeKpi::count());
    }
}
