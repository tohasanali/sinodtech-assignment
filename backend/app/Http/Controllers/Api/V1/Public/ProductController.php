<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProductResource;
use App\Models\Branch;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * List products for third-party e-commerce consumption: SKU, name, price,
     * and available stock only. Stock is aggregated across all branches by
     * default; pass `branch_id` to scope it to a single branch instead.
     */
    public function index(Request $request, StockService $stockService): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
        ]);

        $branch = isset($validated['branch_id']) ? Branch::find($validated['branch_id']) : null;

        $products = Product::orderBy('name')->get();

        $products->each(function (Product $product) use ($stockService, $branch) {
            $product->available_stock = $branch
                ? $stockService->quantityForBranch($product, $branch)
                : $stockService->totalQuantity($product);
        });

        return PublicProductResource::collection($products);
    }
}
