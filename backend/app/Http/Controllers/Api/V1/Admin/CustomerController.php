<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCustomerRequest;
use App\Http\Requests\ReengageBulkRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Notifications\CustomerReengagementNotification;
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

    public function reengage(Customer $customer): JsonResponse
    {
        $customer->notify(new CustomerReengagementNotification);
        $customer->update(['last_contacted_at' => now()]);

        return (new CustomerResource($customer))->response();
    }

    public function reengageBulk(ReengageBulkRequest $request): JsonResponse
    {
        $query = Customer::lost();

        if ($request->filled('customer_ids')) {
            $query->whereIn('id', $request->validated('customer_ids'));
        }

        $customers = $query->get();

        $customers->each(function (Customer $customer) {
            $customer->notify(new CustomerReengagementNotification);
            $customer->update(['last_contacted_at' => now()]);
        });

        return response()->json(['data' => [
            'notified' => $customers->count(),
            'customer_ids' => $customers->pluck('id'),
        ]]);
    }
}
