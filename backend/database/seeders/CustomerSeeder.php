<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CUSTOMER_COUNT = 10;

    /**
     * Seed a small pool of customers for sales to optionally link to.
     */
    public function run(): void
    {
        Customer::factory()->count(self::CUSTOMER_COUNT)->create();
    }
}
