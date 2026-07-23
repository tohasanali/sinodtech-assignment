<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::where('employee_id', $request->user()->id)
            ->with('employee')
            ->orderBy('name');

        if ($request->boolean('lost_only')) {
            $query->lost();
        }

        return CustomerResource::collection($query->get())->response();
    }
}
