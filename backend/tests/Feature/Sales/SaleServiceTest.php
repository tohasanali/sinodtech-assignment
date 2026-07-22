<?php

namespace Tests\Feature\Sales;

use App\Events\SaleCreated;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->saleService = app(SaleService::class);
    }

    private function branchWithStock(int $quantity = 100): array
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => $quantity]);

        return [$branch, $product];
    }

    public function test_was_lost_is_false_for_a_walk_in_sale(): void
    {
        [$branch, $product] = $this->branchWithStock();
        $user = User::factory()->create();

        $this->saleService->process($branch, null, $user, [['product_id' => $product->id, 'quantity' => 1]]);

        Event::assertDispatched(SaleCreated::class, fn (SaleCreated $event) => $event->wasLost === false);
    }

    public function test_was_lost_is_false_for_a_brand_new_customer_with_no_prior_purchase(): void
    {
        [$branch, $product] = $this->branchWithStock();
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->saleService->process($branch, $customer, $user, [['product_id' => $product->id, 'quantity' => 1]]);

        Event::assertDispatched(SaleCreated::class, fn (SaleCreated $event) => $event->wasLost === false);
    }

    public function test_was_lost_is_false_when_last_purchase_is_within_the_threshold(): void
    {
        [$branch, $product] = $this->branchWithStock();
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        Sale::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $this->saleService->process($branch, $customer, $user, [['product_id' => $product->id, 'quantity' => 1]]);

        Event::assertDispatched(SaleCreated::class, fn (SaleCreated $event) => $event->wasLost === false);
    }

    public function test_was_lost_is_true_when_last_purchase_exceeds_the_threshold(): void
    {
        [$branch, $product] = $this->branchWithStock();
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $lastPurchase = now()->subDays(config('crm.lost_customer_days') + 1);
        Sale::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => $lastPurchase,
            'updated_at' => $lastPurchase,
        ]);

        $this->saleService->process($branch, $customer, $user, [['product_id' => $product->id, 'quantity' => 1]]);

        Event::assertDispatched(SaleCreated::class, fn (SaleCreated $event) => $event->wasLost === true);
    }

    public function test_process_creates_a_sale_item_per_line_and_deducts_stock_for_each(): void
    {
        $branch = Branch::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $productA->id, 'quantity' => 20]);
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $productB->id, 'quantity' => 20]);
        $user = User::factory()->create();

        $sale = $this->saleService->process($branch, null, $user, [
            ['product_id' => $productA->id, 'quantity' => 3],
            ['product_id' => $productB->id, 'quantity' => 4],
        ]);

        $this->assertSame(2, SaleItem::where('sale_id', $sale->id)->count());
        $this->assertDatabaseHas('branch_stocks', ['product_id' => $productA->id, 'quantity' => 17]);
        $this->assertDatabaseHas('branch_stocks', ['product_id' => $productB->id, 'quantity' => 16]);
    }

    public function test_unit_price_is_snapshotted_at_process_time(): void
    {
        [$branch, $product] = $this->branchWithStock();
        $user = User::factory()->create();
        $product->update(['price' => 100]);

        $sale = $this->saleService->process($branch, null, $user, [['product_id' => $product->id, 'quantity' => 1]]);

        $product->update(['price' => 999]);

        $this->assertEquals(100, SaleItem::where('sale_id', $sale->id)->first()->unit_price);
    }

    public function test_insufficient_stock_rolls_back_and_never_dispatches_sale_created(): void
    {
        [$branch, $product] = $this->branchWithStock(quantity: 2);
        $user = User::factory()->create();

        $this->expectException(InsufficientStockException::class);

        try {
            $this->saleService->process($branch, null, $user, [['product_id' => $product->id, 'quantity' => 5]]);
        } finally {
            $this->assertSame(0, Sale::count());
            Event::assertNotDispatched(SaleCreated::class);
        }
    }
}
