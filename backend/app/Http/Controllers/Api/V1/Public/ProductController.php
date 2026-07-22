<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Placeholder response proving the `products:read`-scoped Sanctum token
     * reaches a real, ability-gated route. Will be replaced with real
     * StockService-backed data behind a proper API Resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            ['sku' => 'DEMO-001', 'name' => 'Demo Product', 'price' => 19.99, 'stock' => 42],
        ]]);
    }
}
