<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $activeBranchId = $request->hasSession() ? $request->session()->get('active_branch_id') : null;

        $sales = Sale::with(['branch', 'customer', 'user', 'items.product'])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when(
                ! $request->filled('branch_id') && ! $request->user()->isAdmin() && $activeBranchId,
                fn ($q) => $q->where('branch_id', $activeBranchId),
            )
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->get();

        return SaleResource::collection($sales);
    }

    public function store(StoreSaleRequest $request, SaleService $saleService): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $branch = Branch::findOrFail($request->validated('branch_id'));
        } else {
            $activeBranchId = $request->hasSession() ? $request->session()->get('active_branch_id') : null;

            if (! $activeBranchId) {
                return ApiResponse::error('no_active_branch', 'Select a branch first.', 422);
            }

            if ($request->filled('branch_id') && (int) $request->validated('branch_id') !== (int) $activeBranchId) {
                return ApiResponse::error('branch_mismatch', 'The submitted branch does not match your active branch.', 422);
            }

            $branch = Branch::findOrFail($activeBranchId);
        }

        $customer = $request->validated('customer_id')
            ? Customer::findOrFail($request->validated('customer_id'))
            : null;

        $sale = $saleService->process($branch, $customer, $user, $request->validated('items'));

        return (new SaleResource($sale->load('items.product', 'branch', 'customer', 'user')))
            ->response()->setStatusCode(201);
    }
}
