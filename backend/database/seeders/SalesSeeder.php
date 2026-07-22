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
     * branch actually stocks (per the seeded branch_stocks data).
     */
    public function run(): void
    {
        $branches = Branch::all();
        $customers = Customer::all();
        $sellers = User::whereIn('role', [UserRole::Employee, UserRole::Admin])->get();
        $employees = User::where('role', UserRole::Employee)->get();

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

        // Reserve a handful of customers whose *only* sale we'll deliberately
        // backdate past the lost threshold below, so lost-customer detection has
        // deterministic data to work against rather than relying on chance. Excluded
        // from the main random pool so a later, recent sale doesn't undo their
        // "lost" status.
        $lostCandidates = $customers->random(min(3, $customers->count()));
        $regularCustomers = $customers->diff($lostCandidates);

        for ($i = 0; $i < self::SALE_COUNT; $i++) {
            $branch = $branches->random();
            $availableProducts = $productsByBranch[$branch->id];

            if ($availableProducts->isEmpty()) {
                continue;
            }

            $createdAt = fake()->dateTimeBetween('-150 days', 'now');

            // ~70% of sales link to a known customer; the rest are walk-ins
            // (customer_id left null) — both are valid, expected states.
            $customerId = fake()->boolean(70) ? $regularCustomers->random()->id : null;

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

        $lostDays = config('crm.lost_customer_days');
        $branchesWithStock = $branches->filter(fn (Branch $branch) => $productsByBranch[$branch->id]->isNotEmpty());

        foreach ($lostCandidates->values() as $index => $customer) {
            $branch = $branchesWithStock->random();
            $product = $productsByBranch[$branch->id]->random();
            $createdAt = now()->subDays($lostDays + fake()->numberBetween(5, 60));

            $sale = Sale::factory()->create([
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'user_id' => $sellers->random()->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            SaleItem::factory()->create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => fake()->numberBetween(1, 5),
                'unit_price' => $product->price,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Assign about half of the deterministically lost customers to an
            // employee, leaving the rest unassigned — so the assigned/unassigned
            // filter on the lost-customer list has real data on both sides from
            // the start.
            if ($index % 2 === 0 && $employees->isNotEmpty()) {
                $customer->update(['employee_id' => $employees->random()->id]);
            }
        }
    }
}
