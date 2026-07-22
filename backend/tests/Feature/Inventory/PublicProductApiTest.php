<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function scopedToken(): string
    {
        $user = User::factory()->create();

        return $user->createToken('ecommerce-api', ['products:read'])->plainTextToken;
    }

    public function test_default_response_aggregates_stock_across_all_branches(): void
    {
        $product = Product::factory()->create();
        BranchStock::factory()->wellStocked()->create(['product_id' => $product->id, 'quantity' => 100]);
        BranchStock::factory()->lowStock()->create(['product_id' => $product->id, 'quantity' => 5]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->scopedToken())
            ->getJson('/api/v1/public/products');

        $response->assertOk();
        $response->assertJsonFragment([
            'sku' => $product->sku,
            'available_stock' => 105,
        ]);
    }

    public function test_branch_id_scopes_available_stock_to_that_branch_only(): void
    {
        $product = Product::factory()->create();
        $stockedBranch = Branch::factory()->create();
        $emptyBranch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $stockedBranch->id, 'quantity' => 40]);
        // $emptyBranch intentionally has no branch_stocks row for this product.

        $response = $this->withHeader('Authorization', 'Bearer '.$this->scopedToken())
            ->getJson("/api/v1/public/products?branch_id={$emptyBranch->id}");

        $response->assertOk();
        $response->assertJsonFragment(['sku' => $product->sku, 'available_stock' => 0]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->scopedToken())
            ->getJson("/api/v1/public/products?branch_id={$stockedBranch->id}");

        $response->assertOk();
        $response->assertJsonFragment(['sku' => $product->sku, 'available_stock' => 40]);
    }

    public function test_nonexistent_branch_id_fails_validation(): void
    {
        Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->scopedToken())
            ->getJson('/api/v1/public/products?branch_id=999999');

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
    }

    public function test_response_only_exposes_sku_name_price_and_available_stock(): void
    {
        Product::factory()->create(['description' => 'Internal description text']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->scopedToken())
            ->getJson('/api/v1/public/products');

        $response->assertOk();
        $product = $response->json('data.0');

        $this->assertEqualsCanonicalizing(['sku', 'name', 'price', 'available_stock'], array_keys($product));
    }
}
