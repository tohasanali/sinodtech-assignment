<?php

namespace App\Listeners;

use App\Events\SaleCreated;
use App\Models\EmployeeKpi;

class IncrementEmployeeKpi
{
    public function handle(SaleCreated $event): void
    {
        if (! $event->wasLost) {
            return;
        }

        $customer = $event->sale->customer;

        if (! $customer?->employee_id) {
            return;
        }

        EmployeeKpi::create([
            'user_id' => $customer->employee_id,
            'customer_id' => $customer->id,
            'sale_id' => $event->sale->id,
            'points' => config('crm.reactivation_points'),
        ]);
    }
}
