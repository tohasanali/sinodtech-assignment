<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_adjusting_a_product_with_no_existing_stock_creates_a_row(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->admin())
            ->patchJson("/api/v1/admin/products/{$product->id}/branches/{$branch->id}/stock", ['delta' => 30]);

        $response->assertOk();
        $response->assertExactJson(['data' => [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 30,
        ]]);
        $this->assertDatabaseHas('branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 30,
        ]);
    }

    public function test_adjusting_existing_stock_updates_the_quantity(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 50]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/products/{$product->id}/branches/{$branch->id}/stock", ['delta' => 10])
            ->assertJsonPath('data.quantity', 60);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/products/{$product->id}/branches/{$branch->id}/stock", ['delta' => -25])
            ->assertJsonPath('data.quantity', 35);
    }

    public function test_a_delta_that_would_go_negative_is_rejected_without_changing_stock(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        $response = $this->actingAs($this->admin())
            ->patchJson("/api/v1/admin/products/{$product->id}/branches/{$branch->id}/stock", ['delta' => -50]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'insufficient_stock');
        $this->assertDatabaseHas('branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
        ]);
    }

    public function test_missing_delta_fails_validation(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->admin())
            ->patchJson("/api/v1/admin/products/{$product->id}/branches/{$branch->id}/stock", []);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
        $response->assertJsonPath('error.errors.delta.0', 'The delta field is required.');
    }

    public function test_non_admin_is_forbidden_from_adjusting_stock(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->actingAs($employee)
            ->patchJson("/api/v1/admin/products/{$product->id}/branches/{$branch->id}/stock", ['delta' => 5]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'forbidden');
    }
}
