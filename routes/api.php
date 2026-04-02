<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

// ── ADMIN ─────────────────────────────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands',     BrandController::class);

    // Orders (admin xem tất cả + cập nhật trạng thái)
    Route::get  ('orders',               [OrderController::class, 'adminIndex']);
    Route::get  ('orders/{id}',          [OrderController::class, 'adminShow']);
    Route::patch('orders/{id}/status',   [OrderController::class, 'updateStatus']);
});

// ── AUTH ──────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Google OAuth
    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);

    Route::post('/send-otp',       [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp',     [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Cần token
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// ── USER (cần đăng nhập) ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Cart
    Route::get   ('/cart',      [CartController::class, 'index']);
    Route::post  ('/cart',      [CartController::class, 'store']);
    Route::put   ('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart',      [CartController::class, 'clear']);

    // Checkout — đặt hàng
    Route::post('/checkout', [OrderController::class, 'checkout']);

    // Lịch sử đơn hàng
    Route::get  ('/orders',               [OrderController::class, 'index']);
    Route::get  ('/orders/{id}',          [OrderController::class, 'show']);
    Route::patch('/orders/{id}/cancel',   [OrderController::class, 'cancel']);
});