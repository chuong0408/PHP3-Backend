<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand'])->get();
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:50',
            'categories_id' => 'required|exists:categories,id',
            'brand_id'      => 'required|exists:brands,id',
            'description'   => 'required|string',
            'image_url'     => 'required|string',
        ]);

        $product = Product::create($request->all());
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand']);
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'          => 'required|string|max:50',
            'categories_id' => 'required|exists:categories,id',
            'brand_id'      => 'required|exists:brands,id',
            'description'   => 'required|string',
            'image_url'     => 'required|string',
        ]);

        $product->update($request->all());
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Xóa thành công!']);
    }
}