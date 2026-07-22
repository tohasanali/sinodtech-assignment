<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchStock>
 */
class BranchStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(10, 100),
        ];
    }

    /**
     * Indicate that the stock level is high.
     */
    public function wellStocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(100, 500),
        ]);
    }

    /**
     * Indicate that the stock level is low.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(1, 9),
        ]);
    }

    /**
     * Indicate that the stock is depleted.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}
