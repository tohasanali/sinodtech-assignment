<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed branches, products, and per-branch stock levels.
     */
    public function run(): void
    {
        $downtown = Branch::factory()->create([
            'name' => 'Downtown Branch',
            'address' => '12 Market Street',
        ]);

        $uptown = Branch::factory()->create([
            'name' => 'Uptown Branch',
            'address' => '88 Highland Avenue',
        ]);

        $warehouse = Branch::factory()->create([
            'name' => 'Warehouse Branch',
            'address' => '400 Industrial Parkway',
        ]);

        $products = Product::factory()->count(15)->create();

        foreach ($products as $index => $product) {
            // Downtown is fully stocked for every product.
            BranchStock::factory()->wellStocked()->create([
                'branch_id' => $downtown->id,
                'product_id' => $product->id,
            ]);

            // Uptown runs low on most products; a third never carry it at all.
            if ($index % 3 !== 0) {
                BranchStock::factory()->lowStock()->create([
                    'branch_id' => $uptown->id,
                    'product_id' => $product->id,
                ]);
            }

            // Warehouse explicitly shows zero stock for some products; the rest
            // have no stock row at all, distinguishing "out of stock" from
            // "never stocked here" for later branch-scoped queries.
            if ($index % 4 === 0) {
                BranchStock::factory()->outOfStock()->create([
                    'branch_id' => $warehouse->id,
                    'product_id' => $product->id,
                ]);
            }
        }
    }
}
