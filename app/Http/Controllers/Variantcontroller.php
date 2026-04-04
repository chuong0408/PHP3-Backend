<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantOption;
use App\Models\ProductCombinationOption;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    // ── GET /api/admin/products/:productId/variants ───────────────────────────
    // Lấy tất cả variant của 1 sản phẩm, kèm options
    public function index(int $productId)
    {
        $variants = ProductVariant::with('options')
            ->where('product_id', $productId)
            ->orderBy('id')
            ->get();

        return response()->json($variants);
    }

    // ── POST /api/admin/products/:productId/variants ──────────────────────────
    // Tạo variant mới cho sản phẩm
    public function store(Request $request, int $productId)
    {
        $request->validate([
            'variant_name' => 'required|string|max:255',
        ]);

        // Kiểm tra sản phẩm tồn tại
        Product::findOrFail($productId);

        $variant = ProductVariant::create([
            'variant_name' => $request->variant_name,
            'product_id'   => $productId,
        ]);

        $variant->load('options');

        return response()->json($variant, 201);
    }

    // ── PUT /api/admin/variants/:variantId ────────────────────────────────────
    // Đổi tên variant
    public function update(Request $request, int $variantId)
    {
        $request->validate([
            'variant_name' => 'required|string|max:255',
        ]);

        $variant = ProductVariant::findOrFail($variantId);
        $variant->update(['variant_name' => $request->variant_name]);
        $variant->load('options');

        return response()->json($variant);
    }

    // ── DELETE /api/admin/variants/:variantId ─────────────────────────────────
    // Xóa variant + cascade options + combinations
    public function destroy(int $variantId)
    {
        $variant = ProductVariant::with('options')->findOrFail($variantId);

        // Xóa combinations liên quan đến các options của variant này
        $optionIds = $variant->options->pluck('id');
        ProductCombinationOption::whereIn('options_id', $optionIds)->delete();

        // Xóa options
        $variant->options()->delete();

        // Xóa variant
        $variant->delete();

        return response()->json(['message' => 'Đã xóa biến thể']);
    }

    // ── POST /api/admin/variants/:variantId/options ───────────────────────────
    // Thêm giá trị vào variant
    public function storeOption(Request $request, int $variantId)
    {
        $request->validate([
            'option_values' => 'required|string|max:255',
        ]);

        ProductVariant::findOrFail($variantId);

        $option = VariantOption::create([
            'product_variant_id' => $variantId,
            'option_values'      => $request->option_values,
        ]);

        return response()->json($option, 201);
    }

    // ── DELETE /api/admin/options/:optionId ───────────────────────────────────
    // Xóa 1 giá trị (option) + combinations liên quan
    public function destroyOption(int $optionId)
    {
        $option = VariantOption::findOrFail($optionId);

        // Xóa combinations liên quan
        ProductCombinationOption::where('options_id', $optionId)->delete();

        $option->delete();

        return response()->json(['message' => 'Đã xóa giá trị']);
    }

    // ── GET /api/admin/products/:productId/combinations ──────────────────────
    // Lấy danh sách combinations (option + sku) của sản phẩm
    public function getCombinations(int $productId)
    {
        // Lấy tất cả option_id thuộc sản phẩm này
        $optionIds = VariantOption::whereHas('variant', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })->pluck('id');

        $combinations = ProductCombinationOption::with(['option.variant'])
            ->whereIn('options_id', $optionIds)
            ->get();

        return response()->json($combinations);
    }

    // ── POST /api/admin/combinations ──────────────────────────────────────────
    // Gán option vào SKU (tạo combination)
    public function storeCombination(Request $request)
    {
        $request->validate([
            'options_id' => 'required|integer|exists:variant_options,id',
            'sku_code'   => 'required|string|exists:product_skus,sku_code',
        ]);

        // Tránh trùng lặp
        $existing = ProductCombinationOption::where('options_id', $request->options_id)
            ->where('sku_code', $request->sku_code)
            ->first();

        if ($existing) {
            return response()->json($existing);
        }

        $combo = ProductCombinationOption::create([
            'options_id' => $request->options_id,
            'sku_code'   => $request->sku_code,
        ]);

        return response()->json($combo, 201);
    }

    // ── DELETE /api/admin/combinations/:id ────────────────────────────────────
    public function destroyCombination(int $id)
    {
        ProductCombinationOption::findOrFail($id)->delete();
        return response()->json(['message' => 'Đã xóa']);
    }
}