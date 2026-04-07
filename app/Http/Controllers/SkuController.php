<?php

namespace App\Http\Controllers;

use App\Models\ProductSku;
use App\Models\Product;
use Illuminate\Http\Request;

class SkuController extends Controller
{
    public function index(int $productId)
    {
        $skus = ProductSku::where('product_id', $productId)->get();
        return response()->json($skus);
    }

    public function store(Request $request, int $productId)
    {
        $request->validate([
            'sku_code' => 'required|string|max:255|unique:product_skus,sku_code',
            'price'    => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'status'   => 'nullable|in:active,draft,hidden',
        ]);

        Product::findOrFail($productId);

        $sku = ProductSku::create([
            'sku_code'   => $request->sku_code,
            'product_id' => $productId,
            'price'      => $request->price,
            'quantity'   => $request->quantity,
            'status'     => $request->status ?? 'active',
        ]);

        return response()->json($sku, 201);
    }

    public function update(Request $request, string $skuCode)
    {
        $sku = ProductSku::findOrFail($skuCode);

        $request->validate([
            'price'    => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'status'   => 'nullable|in:active,draft,hidden',
        ]);

        $sku->update($request->only(['price', 'quantity', 'status']));
        return response()->json($sku);
    }

    public function destroy(string $skuCode)
    {
        ProductSku::findOrFail($skuCode)->delete();
        return response()->json(['message' => 'Đã xóa SKU']);
    }
}