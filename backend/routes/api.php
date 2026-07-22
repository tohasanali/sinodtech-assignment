<?php

use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\EmployeeController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\SaleController;
use App\Http\Controllers\Api\V1\Admin\StockController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Public\ProductController as PublicProductController;
use App\Models\Customer;
use App\Models\EmployeeKpi;
use App\Models\Product;
use App\Models\Sale;
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

        // Individual routes, not Route::apiResource(): each action needs a different policy
        // ability, and authorizeResource() (which maps those automatically) needs
        // Illuminate\Routing\Controller's $this->middleware(), which our bare Controller
        // base class intentionally doesn't extend.
        Route::get('/admin/products', [AdminProductController::class, 'index'])
            ->middleware('can:viewAny,'.Product::class);
        Route::post('/admin/products', [AdminProductController::class, 'store'])
            ->middleware('can:create,'.Product::class);
        Route::get('/admin/products/{product}', [AdminProductController::class, 'show'])
            ->middleware('can:view,product');
        Route::match(['put', 'patch'], '/admin/products/{product}', [AdminProductController::class, 'update'])
            ->middleware('can:update,product');
        Route::delete('/admin/products/{product}', [AdminProductController::class, 'destroy'])
            ->middleware('can:delete,product');

        Route::patch('/admin/products/{product}/branches/{branch}/stock', [StockController::class, 'adjust'])
            ->middleware('can:update,product');

        Route::post('/admin/sales', [SaleController::class, 'store'])
            ->middleware('can:create,'.Sale::class);

        Route::get('/admin/customers/lost', [CustomerController::class, 'lost'])
            ->middleware('can:viewAny,'.Customer::class);
        Route::get('/admin/customers/{customer}', [CustomerController::class, 'show'])
            ->middleware('can:view,customer');
        Route::patch('/admin/customers/{customer}/assign', [CustomerController::class, 'assign'])
            ->middleware('can:assign,customer');

        Route::get('/admin/employees/kpi', [EmployeeController::class, 'kpi'])
            ->middleware('can:viewAny,'.EmployeeKpi::class);
    });

    // "Public" = third-party e-commerce consumers, not unauthenticated access — scoped
    // tokens exist so a specific partner can be identified, throttled, and revoked.
    Route::get('/public/products', [PublicProductController::class, 'index'])
        ->middleware(['auth:sanctum', 'ability:products:read']);
});
