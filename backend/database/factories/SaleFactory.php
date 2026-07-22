<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-90 days', 'now');

        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
