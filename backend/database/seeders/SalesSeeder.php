<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SALE_COUNT = 40;

    /**
     * Seed historical sales, distributed across branches and the products each
     * branch actually stocks (per Step 3's branch_stocks data).
     */
    public function run(): void
    {
        $branches = Branch::all();
        $customers = Customer::all();
        $sellers = User::whereIn('role', [UserRole::Employee, UserRole::Admin])->get();

        // Products each branch actually carries (a branch_stocks row exists,
        // regardless of current quantity) — a branch can only have sold what
        // it stocks, so sales never reference an uncarried product.
        $productsByBranch = $branches->mapWithKeys(fn (Branch $branch) => [
            $branch->id => BranchStock::query()
                ->where('branch_id', $branch->id)
                ->with('product')
                ->get()
                ->pluck('product', 'product_id'),
        ]);

        for ($i = 0; $i < self::SALE_COUNT; $i++) {
            $branch = $branches->random();
            $availableProducts = $productsByBranch[$branch->id];

            if ($availableProducts->isEmpty()) {
                continue;
            }

            $createdAt = fake()->dateTimeBetween('-90 days', 'now');

            // ~70% of sales link to a known customer; the rest are walk-ins
            // (customer_id left null) — both are valid, expected states.
            $customerId = fake()->boolean(70) ? $customers->random()->id : null;

            $sale = Sale::factory()->create([
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'user_id' => $sellers->random()->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $products = $availableProducts->random(min(fake()->numberBetween(1, 3), $availableProducts->count()));

            foreach ($products as $product) {
                SaleItem::factory()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => fake()->numberBetween(1, 5),
                    'unit_price' => $product->price,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
