<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function adjust(AdjustStockRequest $request, Product $product, Branch $branch, StockService $stockService): JsonResponse
    {
        $stock = $stockService->adjust($product, $branch, $request->validated('delta'));

        return response()->json(['data' => [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => $stock->quantity,
        ]]);
    }
}
