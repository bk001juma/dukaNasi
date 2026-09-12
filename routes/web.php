<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\BaseApp\CategoryController;
use App\Http\Controllers\Api\BaseApp\ShopController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseApp\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/// Public Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    // Add password reset logic here later
})->name('password.email');

// Protected Web Routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
    Route::get('/shops/create', [ShopController::class, 'create'])->name('shops.create');
    Route::post('/shops', [ShopController::class, 'store'])->name('shops.store');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

    //admin

    Route::post('/admin/business-owner', [AdminManagementController::class, 'createBusinessOwner'])
        ->name('business-owner.store');
    Route::get('/admin/business-owners', [AdminManagementController::class, 'getBusinessOwners'])
        ->name('business-owners.index');
    Route::put('/admin/business-owner/{admin}', [AdminManagementController::class, 'updateBusinessOwner'])
        ->name('business-owner.update');
});