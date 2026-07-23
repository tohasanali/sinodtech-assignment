<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
    }

    public function test_quantity_for_branch_is_zero_when_no_stock_row_exists(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();

        $this->assertSame(0, $this->stockService->quantityForBranch($product, $branch));
    }

    public function test_quantity_for_branch_returns_the_existing_row_quantity(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 42]);

        $this->assertSame(42, $this->stockService->quantityForBranch($product, $branch));
    }

    public function test_total_quantity_sums_across_all_branches(): void
    {
        $product = Product::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'quantity' => 30]);
        BranchStock::factory()->create(['product_id' => $product->id, 'quantity' => 15]);

        $this->assertSame(45, $this->stockService->totalQuantity($product));
    }

    public function test_adjust_creates_a_row_when_none_exists(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();

        $stock = $this->stockService->adjust($product, $branch, 20);

        $this->assertSame(20, $stock->quantity);
        $this->assertDatabaseHas('branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 20,
        ]);
    }

    public function test_adjust_adds_to_an_existing_row(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        $stock = $this->stockService->adjust($product, $branch, 5);

        $this->assertSame(15, $stock->quantity);
    }

    public function test_adjust_deducts_from_an_existing_row(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        $stock = $this->stockService->adjust($product, $branch, -4);

        $this->assertSame(6, $stock->quantity);
    }

    public function test_adjust_throws_and_leaves_quantity_unchanged_when_delta_would_go_negative(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 5]);

        try {
            $this->stockService->adjust($product, $branch, -10);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException) {
            // expected
        }

        $this->assertDatabaseHas('branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Simulates two overlapping adjustments — each individually fits the
     * starting quantity, but combined would overdraw it. Proves the
     * lockForUpdate()-guarded read-modify-write never lets the second call
     * see and act on stale data: it's rejected against the quantity left by
     * the first, not the original starting quantity.
     */
    public function test_two_sequential_adjustments_that_together_overdraw_stock_the_second_is_rejected(): void
    {
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        BranchStock::factory()->create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        $stock = $this->stockService->adjust($product, $branch, -6);
        $this->assertSame(4, $stock->quantity);

        try {
            $this->stockService->adjust($product, $branch, -6);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException) {
            // expected
        }

        $this->assertDatabaseHas('branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 4,
        ]);
    }
}
