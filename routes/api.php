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
    Route::delete('products/images/{image}', [ProductController::class, 'destroyImage']); // ← lên trước
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands',     BrandController::class);

    // ── CRUD User ──────────────────────────────────────────────────────────────
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    // ── CRUD Banner ────────────────────────────────────────────────────────────
    Route::apiResource('banners', BannerController::class);
    Route::patch('banners/{banner}/toggle-status', [BannerController::class, 'toggleStatus']);

    // ── CRUD Mã Giảm Giá ──────────────────────────────────────────────────────
    Route::apiResource('coupons', CouponController::class);
    Route::post('coupons/{coupon}/use', [CouponController::class, 'markUsed']);

});

Route::post('/apply-coupon', [CouponController::class, 'apply']);

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