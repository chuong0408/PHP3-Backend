<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\AuthController;

Route::prefix('admin')->group(function () {
    Route::delete('products/images/{image}', [ProductController::class, 'destroyImage']); // ← lên trước
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands',     BrandController::class);
});

Route::prefix('auth')->group(function () {

    // Đăng ký / Đăng nhập thường
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Google OAuth
    Route::get('/google/redirect',  [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback',  [AuthController::class, 'handleGoogleCallback']);

    Route::post('/send-otp',      [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp',    [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Đăng xuất / thông tin user (cần token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});
