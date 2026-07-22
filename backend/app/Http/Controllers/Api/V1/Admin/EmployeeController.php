<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeKpi;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    public function kpi(): JsonResponse
    {
        $summary = EmployeeKpi::query()
            ->selectRaw('user_id, SUM(points) as total_points')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_points')
            ->get()
            ->map(fn (EmployeeKpi $row) => [
                'user_id' => $row->user_id,
                'name' => $row->user->name,
                'points' => (int) $row->total_points,
            ]);

        return response()->json(['data' => $summary]);
    }
}
