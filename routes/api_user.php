<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserProductController;

// ── Sản phẩm (public, không cần đăng nhập) ───────────────────────────────────
Route::prefix('products')->group(function () {
    Route::get('/',    [UserProductController::class, 'index']); // GET /api/products
    Route::get('/{id}',[UserProductController::class, 'show']);  // GET /api/products/{id}
});