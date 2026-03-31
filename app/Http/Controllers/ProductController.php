<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImg;
use App\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // ── Helper: thêm base URL cho ảnh ────────────────────────────────────────
    private function appendImageUrls(Product $product): void
    {
        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

        $product->images->each(function ($img) use ($base) {
            $img->url = $base . '/storage/' . $img->url;
        });

        if ($product->image_url) {
            $product->image_url = $base . '/storage/' . $product->image_url;
        }
    }

    // ── GET /api/admin/products ───────────────────────────────────────────────
    public function index()
    {
        $products = Product::with(['category', 'brand', 'images', 'skus'])->get();

        $products->each(function ($p) {
            $this->appendImageUrls($p);

            // Lấy SKU đầu tiên active để hiển thị giá/tồn kho/status
            $sku = $p->skus->where('status', 'active')->first() ?? $p->skus->first();

            if ($sku) {
                $p->price  = $sku->price;
                $p->stock  = $sku->quantity;
                $p->status = $sku->status;
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
            'status'        => 'nullable|in:active,draft,hidden',
            'is_featured'   => 'nullable|boolean',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            // SKU validation
            'skus'              => 'nullable|array',
            'skus.*.sku_code'   => 'required_with:skus|string|max:255|distinct',
            'skus.*.price'      => 'required_with:skus|numeric|min:0',
            'skus.*.quantity'   => 'required_with:skus|integer|min:0',
            'skus.*.status'     => 'nullable|in:active,draft,hidden',
        ]);

        $product = Product::create([
            'name'          => $request->name,
            'categories_id' => $request->categories_id,
            'brand_id'      => $request->brand_id,
            'description'   => $request->description ?? '',
            'status'        => $request->status ?? 'active',
            'is_featured'   => $request->boolean('is_featured'),
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

        $product->load(['images', 'skus']);
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
            'status'        => 'nullable|in:active,draft,hidden',
            'is_featured'   => 'nullable|boolean',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            // SKU validation
            'skus'              => 'nullable|array',
            'skus.*.sku_code'   => 'required_with:skus|string|max:255|distinct',
            'skus.*.price'      => 'required_with:skus|numeric|min:0',
            'skus.*.quantity'   => 'required_with:skus|integer|min:0',
            'skus.*.status'     => 'nullable|in:active,draft,hidden',
        ]);

        $product->update([
            'name'          => $request->name,
            'categories_id' => $request->categories_id,
            'brand_id'      => $request->brand_id,
            'description'   => $request->description ?? '',
            'status'        => $request->status ?? $product->status,
            'is_featured'   => $request->boolean('is_featured'),
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

                if ($currentCount === 0 && $index === 0) {
                    $product->update(['image_url' => $path]);
                }
            }
        }

        // Cập nhật SKUs: xóa hết rồi tạo lại (upsert đơn giản)
        if ($request->has('skus')) {
            // Giữ lại sku_code cũ nếu cần
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
        }

        $product->load(['images', 'skus']);
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

        // SKUs xóa tự động qua cascade hoặc xóa tay
        $product->skus()->delete();
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
