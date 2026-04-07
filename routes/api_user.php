<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

Route::get('/categories', [CategoryController::class, 'publicIndex']); // GET /api/categories

// ── Sản phẩm (public, không cần đăng nhập) ───────────────────────────────────
Route::prefix('products')->group(function () {
    Route::get('/',     [UserProductController::class, 'index']); // GET /api/products
    Route::get('/{id}', [UserProductController::class, 'show']);  // GET /api/products/{id}
});
// ── Sản phẩm (public, không cần đăng nhập) ───────────────────────────────────
Route::prefix('products')->group(function () {
    Route::get('/',     [UserProductController::class, 'index']); // GET /api/products
    Route::get('/{id}', [UserProductController::class, 'show']);  // GET /api/products/{id}
});

// ── Các route cần đăng nhập ───────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('user')->group(function () {

    // ── Giỏ hàng ─────────────────────────────────────────────────────────────
    Route::get('/cart',           [CartController::class, 'index']);    // GET  /api/user/cart
    Route::post('/cart',           [CartController::class, 'store']);    // POST /api/user/cart
    Route::put('/cart/{id}',      [CartController::class, 'update']);   // PUT  /api/user/cart/{id}
    Route::delete('/cart/{id}',      [CartController::class, 'destroy']);  // DEL  /api/user/cart/{id}

    // ── Đơn hàng ─────────────────────────────────────────────────────────────
    Route::get('/orders',         [OrderController::class, 'index']);   // GET  /api/user/orders
    Route::post('/orders',         [OrderController::class, 'store']);   // POST /api/user/orders
    Route::get('/orders/{id}',    [OrderController::class, 'show']);    // GET  /api/user/orders/{id}
    Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel']); // PATCH /api/user/orders/{id}/cancel

});
