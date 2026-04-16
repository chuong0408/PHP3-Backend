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
use App\Http\Controllers\CouponController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\SkuController;
use App\Http\Controllers\VNPayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\DashboardController;

// ── ADMIN ─────────────────────────────────────────────────────────────────────
Route::prefix('admin')->group(function () {

    // ── Dashboard Stats ───────────────────────────────────────────────────────
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    Route::delete('products/images/{image}', [ProductController::class, 'destroyImage']);
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands',     BrandController::class);

    // ── CRUD User ──────────────────────────────────────────────────────────────
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    // ── CRUD Mã Giảm Giá ──────────────────────────────────────────────────────
    Route::apiResource('coupons', CouponController::class);
    Route::post('coupons/{coupon}/use', [CouponController::class, 'markUsed']);

    // ── Variant CRUD theo product ──────────────────────────────────────────────
    Route::get('products/{productId}/variants',  [VariantController::class, 'index']);
    Route::post('products/{productId}/variants', [VariantController::class, 'store']);
    Route::put('variants/{variantId}',           [VariantController::class, 'update']);
    Route::delete('variants/{variantId}',        [VariantController::class, 'destroy']);

    // Option CRUD
    Route::post('variants/{variantId}/options', [VariantController::class, 'storeOption']);
    Route::delete('options/{optionId}',         [VariantController::class, 'destroyOption']);

    // Combinations (gán option ↔ SKU)
    Route::get('products/{productId}/combinations',  [VariantController::class, 'getCombinations']);
    Route::post('combinations',                       [VariantController::class, 'storeCombination']);
    Route::delete('combinations/{id}',                [VariantController::class, 'destroyCombination']);

    // ── Orders ────────────────────────────────────────────────────────────────
    Route::get('orders',               [AdminOrderController::class, 'index']);
    Route::get('orders/{id}',          [AdminOrderController::class, 'show']);
    Route::patch('orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

    // ── SKU riêng lẻ (CRUD) ───────────────────────────────────────────────────
    Route::get('products/{productId}/skus',  [SkuController::class, 'index']);
    Route::post('products/{productId}/skus', [SkuController::class, 'store']);
    Route::put('skus/{skuCode}',             [SkuController::class, 'update']);
    Route::delete('skus/{skuCode}',          [SkuController::class, 'destroy']);

    // ── Reviews (Admin) ───────────────────────────────────────────────────────
    Route::get('reviews',                [ReviewController::class, 'adminIndex']);
    Route::patch('reviews/{id}/approve', [ReviewController::class, 'approve']);
    Route::patch('reviews/{id}/reject',  [ReviewController::class, 'reject']);
    Route::delete('reviews/{id}',        [ReviewController::class, 'destroy']);

    // ── Contacts (Admin) ──────────────────────────────────────────────────────
    Route::get('contacts/stats',         [ContactController::class, 'stats']);      // ← THÊM MỚI
    Route::get('contacts',               [ContactController::class, 'index']);
    Route::get('contacts/{id}',          [ContactController::class, 'show']);
    Route::post('contacts/{id}/reply',   [ContactController::class, 'reply']);      // ← THÊM MỚI
    Route::patch('contacts/{id}/status', [ContactController::class, 'updateStatus']);
    Route::delete('contacts/{id}',       [ContactController::class, 'destroy']);

    Route::apiResource('banners', BannerController::class);
    Route::patch('banners/{banner}/toggle', [BannerController::class, 'toggle']);
});

Route::post('/apply-coupon', [CouponController::class, 'apply']);
Route::get('/public/coupons', [CouponController::class, 'getPublicCoupons']);

// ── VNPay ──────────────────────────────────────────────────────────────────────
Route::get('/vnpay/callback', [VNPayController::class, 'callback']);

// ── PUBLIC: Đánh giá đã duyệt theo sản phẩm ───────────────────────────────────
Route::get('/reviews/product/{productId}', [ReviewController::class, 'byProduct']);

// ── PUBLIC: Gửi liên hệ (không cần đăng nhập) ─────────────────────────────────
Route::post('/contact', [ContactController::class, 'store']);

// ── AUTH REQUIRED ──────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/vnpay/create-payment', [VNPayController::class, 'createPayment']);

    // PROFILE
    Route::get('/profile',                               [ProfileController::class, 'show']);
    Route::post('/profile',                              [ProfileController::class, 'update']);
    Route::post('/profile/change-password',              [ProfileController::class, 'changePassword']);

    // ĐỊA CHỈ GIAO HÀNG
    Route::get('/profile/addresses',                     [ProfileController::class, 'listAddresses']);
    Route::post('/profile/addresses',                    [ProfileController::class, 'storeAddress']);
    Route::put('/profile/addresses/{id}',                [ProfileController::class, 'updateAddress']);
    Route::delete('/profile/addresses/{id}',             [ProfileController::class, 'deleteAddress']);
    Route::patch('/profile/addresses/{id}/set-default',  [ProfileController::class, 'setDefaultAddress']);

    // ── Orders (User) ──────────────────────────────────────────────────────────
    Route::get('/user/orders',               [OrderController::class, 'index']);
    Route::post('/user/orders',              [OrderController::class, 'store']);
    Route::get('/user/orders/{id}',          [OrderController::class, 'show']);
    Route::patch('/user/orders/{id}/cancel', [OrderController::class, 'cancel']);

    // ── Reviews (User) ─────────────────────────────────────────────────────────
    Route::post('/user/reviews', [ReviewController::class, 'store']);
    Route::get('/user/reviews',  [ReviewController::class, 'myReviews']);
});

// ── PUBLIC ─────────────────────────────────────────────────────────────────────
Route::get('/products',      [ProductController::class, 'publicIndex']);
Route::get('/products/{id}', [\App\Http\Controllers\UserProductController::class, 'show']);
Route::get('/brands',        [BrandController::class,   'index']);
Route::get('/categories',    [CategoryController::class, 'index']);
Route::post('/ai/chat',      [AiChatController::class,  'chat']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);

    Route::post('/send-otp',       [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp',     [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',           [AuthController::class, 'logout']);
        Route::get('/me',                [AuthController::class, 'me']);
        Route::get('/user/my-coupons',   [CouponController::class, 'getMyCoupons']);
        Route::post('/user/save-coupon', [CouponController::class, 'saveCoupon']);
    });
});

Route::prefix('shipping')->group(function () {
    Route::get('provinces',      [ShippingController::class, 'provinces']);
    Route::get('districts',      [ShippingController::class, 'districts']);
    Route::get('wards',          [ShippingController::class, 'wards']);
    Route::post('calculate-fee', [ShippingController::class, 'calculateFee']);
});

Route::get('/banners', [BannerController::class, 'publicIndex']);