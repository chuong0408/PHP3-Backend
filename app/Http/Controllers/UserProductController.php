<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Http\Request;

/**
 * Controller dành riêng cho phía USER (trang chủ, trang sản phẩm).
 * Hoàn toàn TÁCH BIỆT với ProductController của admin.
 * Chỉ có GET, không có quyền chỉnh sửa dữ liệu.
 */
class UserProductController extends Controller
{
    /**
     * GET /api/products
     * Trả về danh sách sản phẩm cho trang chủ / trang danh sách.
     * Hỗ trợ filter: category_id, brand_id, per_page
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'images', 'skus']);

        // Filter theo category
        if ($request->filled('category_id')) {
            $query->where('categories_id', $request->category_id);
        }

        // Filter theo brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Phân trang (mặc định 20, tối đa 100)
        $perPage  = min((int) $request->get('per_page', 20), 100);
        $products = $query->paginate($perPage);

        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

        // Xử lý từng sản phẩm
        $products->getCollection()->transform(function ($product) use ($base) {
            // Fix ảnh đại diện
            if ($product->image_url) {
                $product->image_url = $base . '/storage/' . $product->image_url;
            }

            // Fix ảnh gallery
            $product->images->each(function ($img) use ($base) {
                $img->url = $base . '/storage/' . $img->url;
            });

            // Lấy giá từ SKU đầu tiên có status = active
            // Nếu không có active thì lấy SKU đầu tiên bất kỳ
            $firstSku = $product->skus->where('status', 'active')->first()
                     ?? $product->skus->first();

            $product->price     = $firstSku ? (float) $firstSku->price    : null;
            $product->quantity  = $firstSku ? (int)   $firstSku->quantity  : 0;
            $product->sku_code  = $firstSku ? $firstSku->sku_code          : null;

            // Ẩn mảng skus thô (không cần thiết ở trang danh sách)
            unset($product->skus);

            return $product;
        });

        return response()->json($products);
    }

    /**
     * GET /api/products/{id}
     * Trả về chi tiết 1 sản phẩm kèm đầy đủ SKU, ảnh, variant.
     */
    public function show($id)
    {
        $product = Product::with(['category', 'brand', 'images', 'skus'])
            ->findOrFail($id);

        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

        // Fix ảnh đại diện
        if ($product->image_url) {
            $product->image_url = $base . '/storage/' . $product->image_url;
        }

        // Fix ảnh gallery
        $product->images->each(function ($img) use ($base) {
            $img->url = $base . '/storage/' . $img->url;
        });

        // Lấy giá thấp nhất trong các SKU active
        $activeSku = $product->skus->where('status', 'active');
        $product->price    = $activeSku->min('price');
        $product->quantity = $activeSku->sum('quantity');

        return response()->json($product);
    }
}