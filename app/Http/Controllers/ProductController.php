<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImg;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand', 'images'])->get();

        // Thêm base URL cho ảnh
        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
        $products->each(function ($product) use ($base) {
            $product->images->each(function ($img) use ($base) {
                $img->url = $base . '/storage/' . $img->url;
            });
            if ($product->image_url) {
                $product->image_url = $base . '/storage/' . $product->image_url;
            }
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:50',
            'categories_id' => 'required|exists:categories,id',
            'brand_id'      => 'nullable|exists:brands,id',
            'description'   => 'nullable|string',
            'price'         => 'nullable|numeric|min:0',
            'sku'           => 'nullable|string|max:100',
            'status'        => 'nullable|in:active,draft,hidden',
            'is_featured'   => 'nullable|boolean',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $product = Product::create([
            'name'          => $request->name,
            'categories_id' => $request->categories_id,
            'brand_id'      => $request->brand_id,
            'description'   => $request->description ?? '',
            'image_url'     => '',
        ]);

        // Lưu ảnh vào product_img
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');

                ProductImg::create([
                    'product_id' => $product->id,
                    'url'        => $path,
                    'mota'       => $index === 0 ? 'Ảnh chính' : 'Ảnh ' . ($index + 1),
                ]);

                // Ảnh đầu tiên là ảnh đại diện
                if ($index === 0) {
                    $product->update(['image_url' => $path]);
                }
            }
        }

        return response()->json($product->load('images'), 201);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'images']);

        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
        $product->images->each(function ($img) use ($base) {
            $img->url = $base . '/storage/' . $img->url;
        });
        if ($product->image_url) {
            $product->image_url = $base . '/storage/' . $product->image_url;
        }

        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'          => 'required|string|max:50',
            'categories_id' => 'required|exists:categories,id',
            'brand_id'      => 'nullable|exists:brands,id',
            'description'   => 'nullable|string',
            'price'         => 'nullable|numeric|min:0',
            'sku'           => 'nullable|string|max:100',
            'status'        => 'nullable|in:active,draft,hidden',
            'is_featured'   => 'nullable|boolean',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $product->update([
            'name'          => $request->name,
            'categories_id' => $request->categories_id,
            'brand_id'      => $request->brand_id,
            'description'   => $request->description ?? '',
        ]);

        // Thêm ảnh mới nếu có upload
        if ($request->hasFile('images')) {
            $currentCount = $product->images()->count();

            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');

                ProductImg::create([
                    'product_id' => $product->id,
                    'url'        => $path,
                    'mota'       => 'Ảnh ' . ($currentCount + $index + 1),
                ]);

                // Nếu chưa có ảnh đại diện thì set ảnh đầu tiên
                if ($currentCount === 0 && $index === 0) {
                    $product->update(['image_url' => $path]);
                }
            }
        }

        return response()->json($product->load('images'));
    }

    public function destroy(Product $product)
    {
        // Xóa ảnh trong storage
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->url); // ← bỏ dấu \ ở đầu
        }
        $product->delete();
        return response()->json(['message' => 'Xóa thành công!']);
    }

    // Xóa 1 ảnh riêng lẻ
    public function destroyImage(ProductImg $image)
    {
        Storage::disk('public')->delete($image->url);
        $image->delete();
        return response()->json(['message' => 'Xóa ảnh thành công!']);
    }
}
