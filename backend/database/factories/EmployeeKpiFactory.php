<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\EmployeeKpi;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeKpi>
 */
class EmployeeKpiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'sale_id' => Sale::factory(),
            'points' => fake()->numberBetween(5, 20),
        ];
    }
}
