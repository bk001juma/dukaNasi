<?php

use App\Http\Controllers\Api\BaseApp\AdminManagementController;
use App\Http\Controllers\Api\BaseApp\AuthController;
use App\Http\Controllers\Api\BaseApp\CategoryController;
use App\Http\Controllers\Api\BaseApp\CustomerSupplierController;
use App\Http\Controllers\Api\BaseApp\EmployeeController;
use App\Http\Controllers\Api\BaseApp\ExpenseController;
use App\Http\Controllers\Api\BaseApp\PurchaseController;
use App\Http\Controllers\Api\BaseApp\SaleController;
use App\Http\Controllers\Api\BaseApp\ShopController;
use App\Http\Controllers\Api\BaseApp\StockController;
use App\Http\Controllers\Api\BaseApp\SubscriptionPlanController;
use App\Http\Controllers\Api\BaseApp\UnitController;
use App\Http\Middleware\BaseAppChannelToken;
use App\Http\Middleware\LegacyLoginRateLimit;
use Illuminate\Support\Facades\Route;



// ✅ LOGIN without middleware (outside the group)
Route::post('/v1/baseApp/login', [AuthController::class, 'login'])
    ->middleware(LegacyLoginRateLimit::class);
Route::post('/v2/baseApp/login', [AuthController::class, 'login'])
    ->middleware(LegacyLoginRateLimit::class);

$baseAppRoutes = function (): void {
    

    Route::post('/get-shops', [ShopController::class, 'index']);
    Route::post('/create-shop', [ShopController::class, 'store']);
    Route::post('/get-stocks', [StockController::class, 'index']);

    Route::post('/push-transaction', [SaleController::class, 'store']);
    Route::post('/create-sale', [SaleController::class, 'store']);
    Route::post('/performTransaction', [SaleController::class, 'performTransaction']);
    Route::post('/get-receipt-items', [SaleController::class, 'receiptItems']);
    Route::post('/get-sales', [SaleController::class, 'index']);
    Route::get('/count', [SaleController::class, 'count']);
    Route::get('/paginated', [SaleController::class, 'paginated']);

    Route::get('/units', [UnitController::class, 'index']);
    Route::post('/create-unit', [UnitController::class, 'store']);
    Route::post('/sync-unit', [UnitController::class, 'sync']);

    Route::post('/create-purchase', [PurchaseController::class, 'store']);
    Route::post('/get-purchases', [PurchaseController::class, 'index']);

    Route::post('/create-category', [CategoryController::class, 'store']);
    Route::post('/get-categories', [CategoryController::class, 'index']);

    Route::post('/get-customers', [CustomerSupplierController::class, 'customers']);
    Route::post('/get-suppliers', [CustomerSupplierController::class, 'suppliers']);
    Route::post('/create-customer', [CustomerSupplierController::class, 'storeCustomer']);
    Route::post('/create-supplier', [CustomerSupplierController::class, 'storeSupplier']);

    Route::post('/get-expenses', [ExpenseController::class, 'index']);
    Route::post('/create-expense', [ExpenseController::class, 'store']);
    Route::post('/delete-expense', [ExpenseController::class, 'destroy']);

    Route::post('/create-employee', [EmployeeController::class, 'store']);

    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store']);
    Route::match(['put', 'patch'], '/subscription-plans/{planId}', [SubscriptionPlanController::class, 'update']);
    Route::delete('/subscription-plans/{planId}', [SubscriptionPlanController::class, 'destroy']);

    Route::post('/get-subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/create-subscription-plan', [SubscriptionPlanController::class, 'store']);
    Route::post('/update-subscription-plan', [SubscriptionPlanController::class, 'update']);
    Route::post('/delete-subscription-plan', [SubscriptionPlanController::class, 'destroy']);

    //business owner

    Route::post('/create-business-owner', [AdminManagementController::class, 'createBusinessOwner'])
        ->middleware(BaseAppChannelToken::class);
    Route::get('/business-owners', [AdminManagementController::class, 'getBusinessOwners'])
        ->middleware(BaseAppChannelToken::class);
    Route::put('/business-owner/{admin}', [AdminManagementController::class, 'updateBusinessOwner'])
        ->middleware(BaseAppChannelToken::class);
};

foreach (['v1/baseApp', 'v2/baseApp'] as $baseAppPrefix) {
    Route::prefix($baseAppPrefix)
        ->middleware(BaseAppChannelToken::class)
        ->group($baseAppRoutes);
}
