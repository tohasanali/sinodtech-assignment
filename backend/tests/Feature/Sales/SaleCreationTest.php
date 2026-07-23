<?php

namespace Tests\Feature\Sales;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SaleCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every sale linked to a customer triggers the real SendSaleInvoice
        // listener inline (sync queue in tests) — fake Mail so these
        // endpoint-level tests don't do real PDF/SMTP work.
        Mail::fake();
    }

    private function employee(): User
    {
        return User::factory()->create(['role' => UserRole::Employee]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_employee_can_record_a_sale_with_sufficient_stock(): void
    {
        $employee = $this->employee();
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 50]);

        $response = $this->actingAs($employee)
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branch->id])
            ->postJson('/api/v1/admin/sales', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.items.0.quantity', 5);
        $response->assertJsonPath('data.items.0.product_id', $product->id);

        $this->assertDatabaseHas('sales', [
            'branch_id' => $branch->id,
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => 45,
        ]);
    }

    public function test_sale_without_a_customer_id_succeeds_as_a_walk_in(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10]);

        $response = $this->actingAs($this->employee())
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branch->id])
            ->postJson('/api/v1/admin/sales', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.customer', null);
    }

    public function test_sale_with_a_customer_id_links_the_customer(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10]);

        $response = $this->actingAs($this->employee())
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branch->id])
            ->postJson('/api/v1/admin/sales', [
                'customer_id' => $customer->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.customer.id', $customer->id);
        $this->assertDatabaseHas('sales', ['customer_id' => $customer->id]);
    }

    public function test_insufficient_stock_rejects_the_sale_and_leaves_no_trace(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 3]);

        $response = $this->actingAs($this->employee())
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branch->id])
            ->postJson('/api/v1/admin/sales', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 10],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'insufficient_stock');

        $this->assertSame(0, Sale::count());
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_selling_a_product_the_branch_never_stocked_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        // No branch_stocks row at all for this branch/product pair.

        $response = $this->actingAs($this->employee())
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $branch->id])
            ->postJson('/api/v1/admin/sales', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'insufficient_stock');
        $this->assertSame(0, Sale::count());
    }

    public function test_missing_branch_id_fails_validation_for_admin(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin())->postJson('/api/v1/admin/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
        $response->assertJsonPath('error.errors.branch_id.0', 'The branch id field is required.');
    }

    public function test_employee_without_an_active_branch_receives_a_clear_error(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->employee())->postJson('/api/v1/admin/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'no_active_branch');
    }

    public function test_employee_branch_id_mismatch_with_active_branch_is_rejected(): void
    {
        $activeBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $otherBranch->id, 'product_id' => $product->id, 'quantity' => 10]);

        $response = $this->actingAs($this->employee())
            ->withHeader('Referer', env('FRONTEND_URL', 'http://localhost:8001'))
            ->withSession(['active_branch_id' => $activeBranch->id])
            ->postJson('/api/v1/admin/sales', [
                'branch_id' => $otherBranch->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'branch_mismatch');
        $this->assertSame(0, Sale::count());
    }

    public function test_empty_items_array_fails_validation(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->employee())->postJson('/api/v1/admin/sales', [
            'branch_id' => $branch->id,
            'items' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
    }

    public function test_invalid_product_id_in_items_fails_validation(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->employee())->postJson('/api/v1/admin/sales', [
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => 999999, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
    }

    public function test_non_staff_role_is_forbidden_from_recording_a_sale(): void
    {
        $apiConsumer = User::factory()->create(['role' => UserRole::ApiConsumer]);
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10]);

        $response = $this->actingAs($apiConsumer)->postJson('/api/v1/admin/sales', [
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'forbidden');
    }

    public function test_guest_is_unauthenticated_on_sale_creation(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();

        $response = $this->postJson('/api/v1/admin/sales', [
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'unauthenticated');
    }
}
