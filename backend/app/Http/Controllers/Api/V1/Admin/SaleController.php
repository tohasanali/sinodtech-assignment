<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sales = Sale::with(['branch', 'customer', 'user', 'items.product'])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->latest()
            ->get();

        return SaleResource::collection($sales);
    }

    public function store(StoreSaleRequest $request, SaleService $saleService): JsonResponse
    {
        $branch = Branch::findOrFail($request->validated('branch_id'));
        $customer = $request->validated('customer_id')
            ? Customer::findOrFail($request->validated('customer_id'))
            : null;

        $sale = $saleService->process($branch, $customer, $request->user(), $request->validated('items'));

        return (new SaleResource($sale->load('items.product', 'branch', 'customer', 'user')))
            ->response()->setStatusCode(201);
    }
}
