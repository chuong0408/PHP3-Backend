<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImg;
use App\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // ── Helper: thêm base URL cho ảnh ────────────────────────────────────────
    private function appendImageUrls(Product $product): void
    {
        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

        $product->images->each(function ($img) use ($base) {
            if ($img->url && !str_starts_with($img->url, 'http')) {
                $img->url = $base . '/storage/' . $img->url;
            }
        });

        if ($product->image_url && !str_starts_with($product->image_url, 'http')) {
            $product->image_url = $base . '/storage/' . $product->image_url;
        }
    }

    // ── GET /api/products (public, hỗ trợ search/filter) ─────────────────────
    public function publicIndex(Request $request)
    {
        $query = Product::with(['category', 'brand', 'images', 'skus']);

        // Tìm kiếm theo tên
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Lọc theo danh mục
        if ($request->filled('category_id')) {
            $query->where('categories_id', $request->category_id);
        }

        // Lọc theo thương hiệu
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $perPage = min((int) $request->get('per_page', 48), 100);
        $products = $query->latest()->take($perPage)->get();

        $products->each(function ($p) {
            $this->appendImageUrls($p);
            if ($p->skus->isNotEmpty()) {
                $p->price     = $p->skus->min('price');
                $p->old_price = null;
                $p->stock     = $p->skus->sum('quantity');
            } else {
                $p->price = $p->price ?? 0;
                $p->stock = 0;
            }
        });

        return response()->json($products);
    }

    // ── GET /api/admin/products ───────────────────────────────────────────────
    public function index()
    {
        $products = Product::with(['category', 'brand', 'images', 'skus'])->get();

        $products->each(function ($p) {
            $this->appendImageUrls($p);

            if ($p->skus->isNotEmpty()) {
                // Tổng hợp tất cả SKU
                $p->stock     = $p->skus->sum('quantity');
                $p->min_price = $p->skus->min('price');
                $p->max_price = $p->skus->max('price');
                $p->sku_count = $p->skus->count();
            } else {
                $p->stock     = 0;
                $p->min_price = null;
                $p->max_price = null;
                $p->sku_count = 0;
            }
        });

        return response()->json($products);
    }

    // ── POST /api/admin/products ──────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:50',
            'categories_id' => 'required|exists:categories,id',
            'brand_id'      => 'nullable|exists:brands,id',
            'description'   => 'nullable|string',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            // SKU validation
            'skus'                  => 'nullable|array',
            'skus.*.sku_code'       => 'required_with:skus|string|max:255|distinct',
            'skus.*.price'          => 'required_with:skus|numeric|min:1',
            'skus.*.quantity'       => 'required_with:skus|integer|min:0',
            'skus.*.status'         => 'nullable|in:active,draft,hidden',
        ]);

        $product = Product::create([
            'name'          => $request->name,
            'categories_id' => $request->categories_id,
            'brand_id'      => $request->brand_id,
            'description'   => $request->description ?? '',
            'image_url'     => '',
        ]);

        // Lưu ảnh
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');

                ProductImg::create([
                    'product_id' => $product->id,
                    'url'        => $path,
                    'mota'       => $index === 0 ? 'Ảnh chính' : 'Ảnh ' . ($index + 1),
                ]);

                if ($index === 0) {
                    $product->update(['image_url' => $path]);
                }
            }
        }

        // Lưu SKUs
        if ($request->filled('skus')) {
            foreach ($request->skus as $skuData) {
                ProductSku::create([
                    'sku_code'   => $skuData['sku_code'],
                    'product_id' => $product->id,
                    'price'      => $skuData['price'],
                    'quantity'   => $skuData['quantity'],
                    'status'     => $skuData['status'] ?? 'active',
                ]);
            }
        }

        $product->load(['category', 'brand', 'images', 'skus']);
        $this->appendImageUrls($product);

        return response()->json($product, 201);
    }

    // ── GET /api/admin/products/:id ───────────────────────────────────────────
    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'images', 'skus']);
        $this->appendImageUrls($product);

        return response()->json($product);
    }

    // ── POST /api/admin/products/:id (với _method=PUT) ────────────────────────
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'          => 'required|string|max:50',
            'categories_id' => 'required|exists:categories,id',
            'brand_id'      => 'nullable|exists:brands,id',
            'description'   => 'nullable|string',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            // SKU validation
            'skus'                  => 'nullable|array',
            'skus.*.sku_code'       => 'required_with:skus|string|max:255|distinct',
            'skus.*.price'          => 'required_with:skus|numeric|min:1',
            'skus.*.quantity'       => 'required_with:skus|integer|min:0',
            'skus.*.status'         => 'nullable|in:active,draft,hidden',
        ]);

        $product->update([
            'name'          => $request->name,
            'categories_id' => $request->categories_id,
            'brand_id'      => $request->brand_id,
            'description'   => $request->description ?? '',
        ]);

        // Thêm ảnh mới nếu có
        if ($request->hasFile('images')) {
            $currentCount = $product->images()->count();

            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');

                ProductImg::create([
                    'product_id' => $product->id,
                    'url'        => $path,
                    'mota'       => 'Ảnh ' . ($currentCount + $index + 1),
                ]);

                // Nếu chưa có ảnh nào thì set ảnh đầu tiên làm ảnh chính
                if ($currentCount === 0 && $index === 0) {
                    $product->update(['image_url' => $path]);
                }
            }
        }

        // Cập nhật SKUs: xóa SKU không còn trong danh sách mới, upsert các SKU mới
        if ($request->has('skus')) {
            $newSkuCodes = collect($request->skus)->pluck('sku_code')->toArray();

            // Xóa SKU không còn trong danh sách mới
            $product->skus()->whereNotIn('sku_code', $newSkuCodes)->delete();

            foreach ($request->skus as $skuData) {
                ProductSku::updateOrCreate(
                    ['sku_code' => $skuData['sku_code']],
                    [
                        'product_id' => $product->id,
                        'price'      => $skuData['price'],
                        'quantity'   => $skuData['quantity'],
                        'status'     => $skuData['status'] ?? 'active',
                    ]
                );
            }
        } else {
            // Nếu không gửi skus thì xóa hết SKU cũ
            $product->skus()->delete();
        }

        $product->load(['category', 'brand', 'images', 'skus']);
        $this->appendImageUrls($product);

        return response()->json($product);
    }

    // ── DELETE /api/admin/products/:id ────────────────────────────────────────
    public function destroy(Product $product)
    {
        // Xóa ảnh trong storage
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->url);
        }

        // Xóa SKUs
        $product->skus()->delete();

        // Xóa product_imgs
        $product->images()->delete();

        $product->delete();

        return response()->json(['message' => 'Xóa thành công!']);
    }

    // ── DELETE /api/admin/products/images/:image ──────────────────────────────
    public function destroyImage(ProductImg $image)
    {
        Storage::disk('public')->delete($image->url);
        $image->delete();

        return response()->json(['message' => 'Xóa ảnh thành công!']);
    }
}