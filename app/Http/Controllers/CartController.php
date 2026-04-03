<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * GET /cart
     * Lấy toàn bộ giỏ hàng của user đang đăng nhập,
     * kèm thông tin SKU và sản phẩm.
     */
    public function index()
    {
        $userId = Auth::id();

        $items = Cart::with(['sku.product'])
            ->where('user_id', $userId)
            ->get()
            ->map(function ($item) {
                $sku     = $item->sku;
                $product = $sku?->product;

                return [
                    'id'               => $item->id,
                    'product_sku_code' => $item->product_sku_code,
                    'quantity'         => $item->quantity,
                    'user_id'          => $item->user_id,
                    'price'            => $sku?->price,
                    'stock'            => $sku?->quantity,
                    'sku_status'       => $sku?->status,
                    'product_id'       => $product?->id,
                    'product_name'     => $product?->name,
                    'image_url'        => $product?->image_url,
                ];
            });

        return response()->json(['data' => $items]);
    }

    /**
     * POST /cart
     * Thêm sản phẩm vào giỏ. Nếu SKU đã tồn tại → cộng dồn số lượng.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_sku_code' => 'required|string|exists:product_skus,sku_code',
            'quantity'         => 'required|integer|min:1',
        ]);

        $userId  = Auth::id();
        $skuCode = $request->product_sku_code;
        $qty     = $request->quantity;

        // Kiểm tra tồn kho
        $sku = ProductSku::where('sku_code', $skuCode)->first();
        if ($sku->status !== 'active') {
            return response()->json(['message' => 'Sản phẩm hiện không có sẵn'], 422);
        }

        $item = Cart::where('user_id', $userId)
                    ->where('product_sku_code', $skuCode)
                    ->first();

        if ($item) {
            $newQty = $item->quantity + $qty;
            if ($newQty > $sku->quantity) {
                return response()->json(['message' => 'Vượt quá số lượng tồn kho'], 422);
            }
            $item->quantity = $newQty;
            $item->save();
        } else {
            if ($qty > $sku->quantity) {
                return response()->json(['message' => 'Vượt quá số lượng tồn kho'], 422);
            }
            $item = Cart::create([
                'user_id'          => $userId,
                'product_sku_code' => $skuCode,
                'quantity'         => $qty,
            ]);
        }

        return response()->json(['message' => 'Đã thêm vào giỏ hàng', 'data' => $item], 201);
    }

    /**
     * PUT /cart/{id}
     * Cập nhật số lượng của 1 item trong giỏ.
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $item   = Cart::where('id', $id)->where('user_id', $userId)->firstOrFail();

        // Kiểm tra tồn kho
        $sku = ProductSku::where('sku_code', $item->product_sku_code)->first();
        if ($request->quantity > $sku->quantity) {
            return response()->json(['message' => 'Vượt quá số lượng tồn kho'], 422);
        }

        $item->quantity = $request->quantity;
        $item->save();

        return response()->json(['message' => 'Đã cập nhật', 'data' => $item]);
    }

    /**
     * DELETE /cart/{id}
     * Xoá 1 item khỏi giỏ hàng.
     */
    public function destroy(int $id)
    {
        $userId = Auth::id();
        $item   = Cart::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $item->delete();

        return response()->json(['message' => 'Đã xoá sản phẩm khỏi giỏ hàng']);
    }

    /**
     * DELETE /cart
     * Xoá toàn bộ giỏ hàng của user.
     */
    public function clear()
    {
        $userId = Auth::id();
        Cart::where('user_id', $userId)->delete();

        return response()->json(['message' => 'Đã xoá toàn bộ giỏ hàng']);
    }
}