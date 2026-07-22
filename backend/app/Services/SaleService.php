<?php

namespace App\Services;

use App\Events\SaleCreated;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function process(Branch $branch, ?Customer $customer, User $user, array $items): Sale
    {
        // Computed before any insert: checking recency after the sale exists
        // would let the new sale satisfy its own "has a recent purchase" check.
        $wasLost = $this->customerWasLost($customer);

        $sale = DB::transaction(function () use ($branch, $customer, $user, $items) {
            $sale = Sale::create([
                'branch_id' => $branch->id,
                'customer_id' => $customer?->id,
                'user_id' => $user->id,
            ]);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Throws InsufficientStockException on insufficient stock, which
                // rolls back this entire transaction (sale and any prior items).
                $this->stockService->adjust($product, $branch, -$item['quantity']);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ]);
            }

            return $sale;
        });

        event(new SaleCreated($sale, $wasLost));

        return $sale;
    }

    /**
     * Same lost-customer definition Step 9 formalizes into a reusable
     * Customer query scope: at least one prior purchase, and the most recent
     * one older than the configured threshold.
     */
    private function customerWasLost(?Customer $customer): bool
    {
        if (! $customer) {
            return false;
        }

        $lastPurchaseAt = $customer->sales()->max('created_at');

        if (! $lastPurchaseAt) {
            return false;
        }

        return Carbon::parse($lastPurchaseAt)->lt(now()->subDays(config('crm.lost_customer_days')));
    }
}
