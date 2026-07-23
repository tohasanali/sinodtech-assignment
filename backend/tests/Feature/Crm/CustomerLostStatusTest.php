<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLostStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_past_threshold_with_a_prior_purchase_is_lost(): void
    {
        $customer = Customer::factory()->create();
        Sale::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(config('crm.lost_customer_days') + 1),
            'updated_at' => now()->subDays(config('crm.lost_customer_days') + 1),
        ]);

        $this->assertTrue($customer->isLost());
        $this->assertTrue(Customer::lost()->whereKey($customer->id)->exists());
    }

    public function test_customer_past_threshold_with_no_prior_purchase_is_not_lost(): void
    {
        $customer = Customer::factory()->create();

        $this->assertFalse($customer->isLost());
        $this->assertFalse(Customer::lost()->whereKey($customer->id)->exists());
    }

    public function test_recent_customer_is_not_lost(): void
    {
        $customer = Customer::factory()->create();
        Sale::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->assertFalse($customer->isLost());
        $this->assertFalse(Customer::lost()->whereKey($customer->id)->exists());
    }
}
