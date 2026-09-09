<?php

use App\Http\Controllers\Api\BaseApp\AuthController;
use App\Http\Controllers\Api\BaseApp\CategoryController;
use App\Http\Controllers\Api\BaseApp\CustomerSupplierController;
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

$baseAppRoutes = function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(LegacyLoginRateLimit::class);

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

    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store']);
    Route::match(['put', 'patch'], '/subscription-plans/{planId}', [SubscriptionPlanController::class, 'update']);
    Route::delete('/subscription-plans/{planId}', [SubscriptionPlanController::class, 'destroy']);

    Route::post('/get-subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/create-subscription-plan', [SubscriptionPlanController::class, 'store']);
    Route::post('/update-subscription-plan', [SubscriptionPlanController::class, 'update']);
    Route::post('/delete-subscription-plan', [SubscriptionPlanController::class, 'destroy']);
};

foreach (['v1/baseApp', 'v2/baseApp'] as $baseAppPrefix) {
    Route::prefix($baseAppPrefix)
        ->middleware(BaseAppChannelToken::class)
        ->group($baseAppRoutes);
}
