<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $base   = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

        $items = DB::table('cart as c')
            ->join('product_skus as ps', 'c.product_sku_code', '=', 'ps.sku_code')
            ->join('products as p', 'ps.product_id', '=', 'p.id')
            ->where('c.user_id', $userId)
            ->select(
                'c.id',
                'c.product_sku_code',
                'c.quantity',
                'c.user_id',
                'ps.price',
                'ps.quantity as stock',
                'ps.status as sku_status',
                'p.name as product_name',
                'p.id as product_id',
                'p.image_url'
            )
            ->get()
            ->map(function ($item) use ($base) {
                // Thêm full URL cho ảnh
                if ($item->image_url && !str_starts_with($item->image_url, 'http')) {
                    $item->image_url = $base . '/storage/' . $item->image_url;
                }
                return $item;
            });

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_sku_code' => 'required|string|exists:product_skus,sku_code',
            'quantity'         => 'required|integer|min:1',
        ]);

        $userId  = Auth::id();
        $skuCode = $request->product_sku_code;
        $qty     = $request->quantity;

        $sku = DB::table('product_skus')->where('sku_code', $skuCode)->first();
        if (!$sku || $sku->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không khả dụng'], 422);
        }

        $existing = DB::table('cart')
            ->where('user_id', $userId)
            ->where('product_sku_code', $skuCode)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $qty;
            if ($newQty > $sku->quantity) {
                return response()->json(['success' => false, 'message' => 'Vượt quá số lượng tồn kho'], 422);
            }
            DB::table('cart')->where('id', $existing->id)->update(['quantity' => $newQty]);
            $cartId = $existing->id;
        } else {
            if ($qty > $sku->quantity) {
                return response()->json(['success' => false, 'message' => 'Vượt quá số lượng tồn kho'], 422);
            }
            $cartId = DB::table('cart')->insertGetId([
                'product_sku_code' => $skuCode,
                'quantity'         => $qty,
                'user_id'          => $userId,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Đã thêm vào giỏ hàng', 'cart_id' => $cartId], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $userId = Auth::id();
        $item   = DB::table('cart')->where('id', $id)->where('user_id', $userId)->first();
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mục giỏ hàng'], 404);
        }

        $sku = DB::table('product_skus')->where('sku_code', $item->product_sku_code)->first();
        if ($request->quantity > $sku->quantity) {
            return response()->json(['success' => false, 'message' => 'Vượt quá số lượng tồn kho'], 422);
        }

        DB::table('cart')->where('id', $id)->update(['quantity' => $request->quantity]);
        return response()->json(['success' => true, 'message' => 'Đã cập nhật số lượng']);
    }

    public function destroy($id)
    {
        $userId  = Auth::id();
        $deleted = DB::table('cart')->where('id', $id)->where('user_id', $userId)->delete();
        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mục giỏ hàng'], 404);
        }
        return response()->json(['success' => true, 'message' => 'Đã xoá khỏi giỏ hàng']);
    }

    public function clear()
    {
        $userId = Auth::id();
        DB::table('cart')->where('user_id', $userId)->delete();
        return response()->json(['success' => true, 'message' => 'Đã xoá toàn bộ giỏ hàng']);
    }
}