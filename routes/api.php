<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;

Route::prefix('admin')->group(function () {
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands',     BrandController::class);
});