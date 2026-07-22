<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::with('branchStocks.branch')->orderBy('name')->get()
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('branchStocks.branch'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('branchStocks.branch'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
