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

class ActiveBranchSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_set_their_active_branch_to_an_assigned_branch(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $branch = Branch::factory()->create();
        $employee->branches()->attach($branch->id);

        $response = $this->actingAs($employee)
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->postJson('/api/v1/session/branch', [
                'branch_id' => $branch->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.active_branch_id', $branch->id);
    }

    public function test_employee_is_rejected_setting_a_branch_they_are_not_assigned_to(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $branch = Branch::factory()->create();
        // No user_branches row attaching $employee to $branch.

        $response = $this->actingAs($employee)->postJson('/api/v1/session/branch', [
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
    }

    public function test_employee_product_listing_narrows_to_active_branch_stock_only(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branchA->id, 'product_id' => $product->id, 'quantity' => 5]);
        BranchStock::factory()->create(['branch_id' => $branchB->id, 'product_id' => $product->id, 'quantity' => 9]);

        $response = $this->actingAs($employee)
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branchA->id])
            ->getJson('/api/v1/admin/products');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.0.stock');
        $response->assertJsonPath('data.0.stock.0.branch_id', $branchA->id);
    }

    public function test_employee_sales_history_defaults_to_active_branch_when_unfiltered(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branchA->id, 'product_id' => $productA->id, 'quantity' => 10]);
        BranchStock::factory()->create(['branch_id' => $branchB->id, 'product_id' => $productB->id, 'quantity' => 10]);

        $saleA = $this->actingAs($employee)
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branchA->id])
            ->postJson('/api/v1/admin/sales', ['items' => [['product_id' => $productA->id, 'quantity' => 1]]])
            ->json('data');

        $this->actingAs($employee)
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branchB->id])
            ->postJson('/api/v1/admin/sales', ['items' => [['product_id' => $productB->id, 'quantity' => 1]]])
            ->assertCreated();

        $this->assertSame(2, Sale::count());

        $response = $this->actingAs($employee)
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branchA->id])
            ->getJson('/api/v1/admin/sales');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $saleA['id']);
    }

    public function test_employee_can_switch_active_branch_and_subsequent_sale_uses_the_new_branch(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $employee->branches()->attach([$branchA->id, $branchB->id]);
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branchA->id, 'product_id' => $productA->id, 'quantity' => 10]);
        BranchStock::factory()->create(['branch_id' => $branchB->id, 'product_id' => $productB->id, 'quantity' => 10]);
        $referer = ['Referer' => env('FRONTEND_URL', 'http://localhost:8001')];

        $session = $this->actingAs($employee);
        $session->postJson('/api/v1/session/branch', ['branch_id' => $branchA->id], $referer)->assertOk();
        $firstSale = $session->postJson('/api/v1/admin/sales', [
            'items' => [['product_id' => $productA->id, 'quantity' => 1]],
        ], $referer)->assertCreated()->json('data');

        $this->assertSame($branchA->id, $firstSale['branch']['id']);

        $session->postJson('/api/v1/session/branch', ['branch_id' => $branchB->id], $referer)->assertOk();
        $secondSale = $session->postJson('/api/v1/admin/sales', [
            'items' => [['product_id' => $productB->id, 'quantity' => 1]],
        ], $referer)->assertCreated()->json('data');

        $this->assertSame($branchB->id, $secondSale['branch']['id']);
    }
}
