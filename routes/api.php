<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\SkuController;
use App\Http\Controllers\VNPayController;


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
    // Route::apiResource('banners', BannerController::class);
    // Route::patch('banners/{banner}/toggle-status', [BannerController::class, 'toggleStatus']);

    // ── CRUD Mã Giảm Giá ──────────────────────────────────────────────────────
    Route::apiResource('coupons', CouponController::class);
    Route::post('coupons/{coupon}/use', [CouponController::class, 'markUsed']);
    // Variant CRUD theo product
    Route::get('products/{productId}/variants', [VariantController::class, 'index']);
    Route::post('products/{productId}/variants', [VariantController::class, 'store']);
    Route::put('variants/{variantId}',          [VariantController::class, 'update']);
    Route::delete('variants/{variantId}',          [VariantController::class, 'destroy']);

    // Option CRUD
    Route::post('variants/{variantId}/options',  [VariantController::class, 'storeOption']);
    Route::delete('options/{optionId}',            [VariantController::class, 'destroyOption']);

    // Combinations (gán option ↔ SKU)
    Route::get('products/{productId}/combinations', [VariantController::class, 'getCombinations']);
    Route::post('combinations',                      [VariantController::class, 'storeCombination']);
    Route::delete('combinations/{id}',                 [VariantController::class, 'destroyCombination']);

    // ── Orders ────────────────────────────────────────────────────────────────
    Route::get('orders',                 [AdminOrderController::class, 'index']);
    Route::get('orders/{id}',            [AdminOrderController::class, 'show']);
    Route::patch('orders/{id}/status',   [AdminOrderController::class, 'updateStatus']);

    // SKU riêng lẻ (CRUD)
    Route::get('products/{productId}/skus',    [SkuController::class, 'index']);
    Route::post('products/{productId}/skus',   [SkuController::class, 'store']);
    Route::put('skus/{skuCode}',               [SkuController::class, 'update']);
    Route::delete('skus/{skuCode}',            [SkuController::class, 'destroy']);
});

Route::post('/apply-coupon', [CouponController::class, 'apply']);
Route::get('/public/coupons', [CouponController::class, 'getPublicCoupons']);

// ── VNPay ──────────────────────────────────────────────────────────────────
Route::get('/vnpay/callback', [VNPayController::class, 'callback']); // không cần auth (VNPay gọi)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/vnpay/create-payment', [VNPayController::class, 'createPayment']);
});

Route::get('/products',   [ProductController::class, 'publicIndex']);
Route::get('/products/{id}',   [\App\Http\Controllers\UserProductController::class, 'show']);
Route::get('/brands',     [BrandController::class,   'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/ai/chat', [AiChatController::class, 'chat']);

Route::get('/brands',     [BrandController::class,   'index']);
Route::get('/categories', [CategoryController::class, 'index']);

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
        Route::get('/user/my-coupons',   [CouponController::class, 'getMyCoupons']);
        Route::post('/user/save-coupon', [CouponController::class, 'saveCoupon']);
    });
});
