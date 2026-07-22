<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['sales.items.product', 'sales.branch', 'sales.user', 'employee']);

        return (new CustomerResource($customer))->response();
    }

    public function lost(Request $request): JsonResponse
    {
        $query = Customer::lost()->with('employee');

        if ($request->has('assigned')) {
            $request->boolean('assigned')
                ? $query->whereNotNull('employee_id')
                : $query->whereNull('employee_id');
        }

        return CustomerResource::collection($query->get())->response();
    }

    public function assign(AssignCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update(['employee_id' => $request->validated('employee_id')]);

        return (new CustomerResource($customer->fresh('employee')))->response();
    }
}
