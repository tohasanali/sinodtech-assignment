<?php

namespace Tests\Feature\Sales;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function sellAs(User $seller, Branch $branch, Product $product): Sale
    {
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10]);

        $response = $this->actingAs($seller)->postJson('/api/v1/admin/sales', [
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated();

        return Sale::latest()->firstOrFail();
    }

    public function test_employee_only_sees_their_own_sales(): void
    {
        $employeeA = User::factory()->create(['role' => UserRole::Employee]);
        $employeeB = User::factory()->create(['role' => UserRole::Employee]);
        $branch = Branch::factory()->create();

        $saleA = $this->sellAs($employeeA, $branch, Product::factory()->create());
        $this->sellAs($employeeB, $branch, Product::factory()->create());

        $response = $this->actingAs($employeeA)->getJson('/api/v1/admin/sales');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $saleA->id);
    }

    public function test_admin_sees_every_employees_sales(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employeeA = User::factory()->create(['role' => UserRole::Employee]);
        $employeeB = User::factory()->create(['role' => UserRole::Employee]);
        $branch = Branch::factory()->create();

        $this->sellAs($employeeA, $branch, Product::factory()->create());
        $this->sellAs($employeeB, $branch, Product::factory()->create());
        $this->sellAs($admin, $branch, Product::factory()->create());

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/sales');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }
}
