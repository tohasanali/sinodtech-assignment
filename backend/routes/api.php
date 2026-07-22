<?php

use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Public\ProductController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Unversioned on purpose: infrastructure/monitoring endpoints (load balancers,
// uptime checks, liveness probes) need a fixed path that doesn't change across
// API versions.
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        Route::get('/admin/users', [UserController::class, 'index'])
            ->middleware('can:viewAny,'.User::class);
    });

    // "Public" = third-party e-commerce consumers, not unauthenticated access — scoped
    // tokens exist so a specific partner can be identified, throttled, and revoked.
    Route::get('/public/products', [ProductController::class, 'index'])
        ->middleware(['auth:sanctum', 'ability:products:read']);
});
