<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Notifications\CustomerReengagementNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendCustomerReengagementNotifications extends Command
{
    protected $signature = 'customers:reengage';

    protected $description = 'Notify lost customers who have not been contacted in the last 7 days';

    public function handle(): int
    {
        $customers = Customer::lost()
            ->where(fn (Builder $query) => $query->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', now()->subDays(config('crm.recontact_cooldown_days'))))
            ->get();

        $customers->each(function (Customer $customer) {
            $customer->notify(new CustomerReengagementNotification);
            $customer->update(['last_contacted_at' => now()]);
        });

        $this->info("Notified {$customers->count()} customers.");

        return self::SUCCESS;
    }
}
