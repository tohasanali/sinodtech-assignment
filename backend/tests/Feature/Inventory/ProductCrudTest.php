<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_admin_can_list_products_with_per_branch_stock_breakdown(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create(['name' => 'Downtown Branch']);
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 25]);

        $response = $this->actingAs($this->admin())->getJson('/api/v1/admin/products');

        $response->assertOk();
        $response->assertJsonFragment([
            'branch_id' => $branch->id,
            'branch_name' => 'Downtown Branch',
            'quantity' => 25,
        ]);
    }

    public function test_admin_can_view_a_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin())->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $product->id);
        $response->assertJsonPath('data.sku', $product->sku);
    }

    public function test_admin_can_create_a_product(): void
    {
        $payload = [
            'name' => 'Wireless Mouse',
            'sku' => 'WM-1000',
            'price' => 19.99,
            'description' => 'A wireless mouse.',
        ];

        $response = $this->actingAs($this->admin())->postJson('/api/v1/admin/products', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.sku', 'WM-1000');
        $this->assertDatabaseHas('products', ['sku' => 'WM-1000']);
    }

    public function test_creating_a_product_with_a_duplicate_sku_fails_validation(): void
    {
        Product::factory()->create(['sku' => 'DUP-001']);

        $response = $this->actingAs($this->admin())->postJson('/api/v1/admin/products', [
            'name' => 'Another Product',
            'sku' => 'DUP-001',
            'price' => 9.99,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
        $response->assertJsonPath('error.errors.sku.0', 'The sku has already been taken.');
    }

    public function test_admin_can_partially_update_a_product_without_its_own_sku_colliding(): void
    {
        $product = Product::factory()->create(['sku' => 'KEEP-001', 'price' => 10]);

        $response = $this->actingAs($this->admin())
            ->patchJson("/api/v1/admin/products/{$product->id}", ['price' => 15]);

        $response->assertOk();
        $response->assertJsonPath('data.sku', 'KEEP-001');
        // A whole-number float (15.0) round-trips through JSON as 15 (int) —
        // PHP's json_encode drops the trailing .0 unless JSON_PRESERVE_ZERO_FRACTION
        // is set, which Laravel's default json response doesn't set.
        $response->assertJsonPath('data.price', 15);
    }

    public function test_admin_can_delete_a_product_and_it_is_then_gone(): void
    {
        $product = Product::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->deleteJson("/api/v1/admin/products/{$product->id}")->assertNoContent();
        $this->actingAs($admin)->getJson("/api/v1/admin/products/{$product->id}")->assertNotFound();
    }

    public function test_non_admin_is_forbidden_from_creating_a_product(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $response = $this->actingAs($employee)->postJson('/api/v1/admin/products', [
            'name' => 'Should Not Be Created',
            'sku' => 'NOPE-001',
            'price' => 1,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'forbidden');
    }

    public function test_guest_is_unauthenticated_on_product_index(): void
    {
        $response = $this->getJson('/api/v1/admin/products');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'unauthenticated');
    }
}
