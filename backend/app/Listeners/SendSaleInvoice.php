<?php

namespace App\Listeners;

use App\Events\SaleCreated;
use App\Mail\SaleInvoiceMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendSaleInvoice implements ShouldQueue
{
    public function handle(SaleCreated $event): void
    {
        $sale = $event->sale;

        if (! $sale->customer_id) {
            return;
        }

        $sale->loadMissing('items.product', 'branch', 'customer', 'user');

        Mail::to($sale->customer->email)->send(new SaleInvoiceMail($sale));
    }
}
