<?php

use App\Http\Controllers\Api\V1\Admin\BranchController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\EmployeeController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\SaleController;
use App\Http\Controllers\Api\V1\Admin\StockController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Employee\CustomerController as EmployeeCustomerController;
use App\Http\Controllers\Api\V1\Public\ProductController as PublicProductController;
use App\Models\Branch;
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
        Route::post('/session/branch', [SessionController::class, 'setActiveBranch']);

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

        Route::get('/admin/branches', [BranchController::class, 'index'])
            ->middleware('can:viewAny,'.Branch::class);

        Route::get('/admin/sales', [SaleController::class, 'index'])
            ->middleware('can:viewAny,'.Sale::class);
        Route::post('/admin/sales', [SaleController::class, 'store'])
            ->middleware('can:create,'.Sale::class);

        Route::get('/admin/customers', [CustomerController::class, 'index'])
            ->middleware('can:viewAny,'.Customer::class);
        Route::post('/admin/customers', [CustomerController::class, 'store'])
            ->middleware('can:create,'.Customer::class);
        Route::get('/admin/customers/lost', [CustomerController::class, 'lost'])
            ->middleware('can:viewAny,'.Customer::class);
        Route::get('/admin/customers/{customer}', [CustomerController::class, 'show'])
            ->middleware('can:view,customer');
        Route::match(['put', 'patch'], '/admin/customers/{customer}', [CustomerController::class, 'update'])
            ->middleware('can:update,customer');
        Route::delete('/admin/customers/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('can:delete,customer');
        Route::patch('/admin/customers/{customer}/assign', [CustomerController::class, 'assign'])
            ->middleware('can:assign,customer');
        Route::post('/admin/customers/reengage/bulk', [CustomerController::class, 'reengageBulk'])
            ->middleware('can:reengageAny,'.Customer::class);
        Route::post('/admin/customers/{customer}/reengage', [CustomerController::class, 'reengage'])
            ->middleware('can:reengage,customer');

        Route::get('/admin/employees/kpi', [EmployeeController::class, 'kpi'])
            ->middleware('can:viewAny,'.EmployeeKpi::class);

        Route::get('/employee/customers', [EmployeeCustomerController::class, 'index'])
            ->middleware('can:viewAny,'.Customer::class);
    });

    // "Public" = third-party e-commerce consumers, not unauthenticated access — scoped
    // tokens exist so a specific partner can be identified, throttled, and revoked.
    Route::get('/public/products', [PublicProductController::class, 'index'])
        ->middleware(['auth:sanctum', 'ability:products:read']);
});
