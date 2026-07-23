<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetActiveBranchRequest;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function setActiveBranch(SetActiveBranchRequest $request): JsonResponse
    {
        $branchId = $request->validated('branch_id');

        $request->session()->put('active_branch_id', $branchId);

        return response()->json(['data' => ['active_branch_id' => $branchId]]);
    }
}
