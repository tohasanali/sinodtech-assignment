<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function quantityForBranch(Product $product, Branch $branch): int
    {
        return (int) BranchStock::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $branch->id)
            ->value('quantity');
    }

    public function totalQuantity(Product $product): int
    {
        return (int) BranchStock::query()
            ->where('product_id', $product->id)
            ->sum('quantity');
    }

    /**
     * Adjust a product's stock at a branch by the given delta (positive to add,
     * negative to deduct). Locks the row for the life of the transaction so
     * concurrent adjustments can't race past zero.
     *
     * Known accepted limitation: lockForUpdate() only locks a row that already
     * exists, so two concurrent requests both writing the very first stock for
     * the same product/branch pair could still race on the underlying unique
     * constraint. Left unhandled by choice — steady-state usage always operates
     * on rows that already exist (seeded or previously adjusted).
     */
    public function adjust(Product $product, Branch $branch, int $delta): BranchStock
    {
        return DB::transaction(function () use ($product, $branch, $delta) {
            $stock = BranchStock::query()
                ->where('product_id', $product->id)
                ->where('branch_id', $branch->id)
                ->lockForUpdate()
                ->first();

            $currentQuantity = $stock->quantity ?? 0;
            $newQuantity = $currentQuantity + $delta;

            if ($newQuantity < 0) {
                throw new InsufficientStockException(
                    "Insufficient stock for product [{$product->id}] at branch [{$branch->id}]: ".
                    "has {$currentQuantity} available, requested ".abs($delta)
                );
            }

            if ($stock) {
                $stock->update(['quantity' => $newQuantity]);

                return $stock;
            }

            return BranchStock::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'quantity' => $newQuantity,
            ]);
        });
    }
}
