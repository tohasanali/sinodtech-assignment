<?php

namespace Tests\Feature\Sales;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConcurrentSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /**
     * Simulates two overlapping sale requests for the same branch/product,
     * where each individually fits the available stock but combined would
     * overdraw it. SQLite `:memory:` (this suite's test DB) can't host real
     * concurrent connections against the same data the way MySQL can, so
     * this proves the guard's correctness (never goes negative, second
     * request cleanly rejected) rather than literal thread-safety.
     */
    public function test_second_of_two_overlapping_sales_is_cleanly_rejected_and_stock_never_goes_negative(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10]);

        $employeeA = User::factory()->create(['role' => UserRole::Employee]);
        $employeeB = User::factory()->create(['role' => UserRole::Employee]);
        $referer = ['Referer' => env('FRONTEND_URL', 'http://localhost:8001')];

        $firstResponse = $this->actingAs($employeeA)
            ->withSession(['active_branch_id' => $branch->id])
            ->postJson('/api/v1/admin/sales', [
                'items' => [['product_id' => $product->id, 'quantity' => 6]],
            ], $referer);

        $firstResponse->assertCreated();

        $secondResponse = $this->actingAs($employeeB)
            ->withSession(['active_branch_id' => $branch->id])
            ->postJson('/api/v1/admin/sales', [
                'items' => [['product_id' => $product->id, 'quantity' => 6]],
            ], $referer);

        $secondResponse->assertStatus(422);
        $secondResponse->assertJsonPath('error.code', 'insufficient_stock');

        $this->assertSame(1, Sale::count());
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
    }
}
