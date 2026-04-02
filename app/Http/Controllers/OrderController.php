<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSku;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // ─── ĐẶT HÀNG ────────────────────────────────────────────────────────────
    // POST /api/checkout
    public function checkout(Request $request)
    {
        $user = $request->user();

        // Validate
        $request->validate([
            'email'   => 'required|email',
            'phone'   => 'required',
            'address' => 'required|string',
            'payment' => 'required|string',
            'items'   => 'required|array|min:1',
            'items.*.product_sku_code' => 'required|string|exists:product_skus,sku_code',
            'items.*.quantity'         => 'required|integer|min:1',
        ], [
            'email.required'   => 'Vui lòng nhập email.',
            'phone.required'   => 'Vui lòng nhập số điện thoại.',
            'address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'payment.required' => 'Vui lòng chọn phương thức thanh toán.',
            'items.required'   => 'Giỏ hàng trống.',
        ]);

        DB::beginTransaction();
        try {
            // Tính tổng tiền & kiểm tra tồn kho
            $total = 0;
            $items = [];

            foreach ($request->items as $item) {
                $sku = ProductSku::where('sku_code', $item['product_sku_code'])->first();

                if (!$sku) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Sản phẩm {$item['product_sku_code']} không tồn tại.",
                    ], 422);
                }

                if ($sku->quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Sản phẩm {$sku->sku_code} không đủ số lượng trong kho.",
                    ], 422);
                }

                $total += $sku->price * $item['quantity'];
                $items[] = ['sku' => $sku, 'quantity' => $item['quantity']];
            }

            // Tạo đơn hàng
            $order = Order::create([
                'user_id'    => $user->id,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'address'    => $request->address,
                'total'      => $total,
                'payment'    => $request->payment,
                'status'     => 'pending',
                'created_at' => now(),
            ]);

            // Tạo order_details & trừ tồn kho
            foreach ($items as $item) {
                OrderDetail::create([
                    'product_sku_code' => $item['sku']->sku_code,
                    'orders_id'        => $order->id,
                    'quantity'         => $item['quantity'],
                ]);

                // Trừ tồn kho
                $item['sku']->decrement('quantity', $item['quantity']);
            }

            // Xoá giỏ hàng sau khi đặt hàng thành công
            if (class_exists(Cart::class)) {
                Cart::where('user_id', $user->id)->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'data'    => $this->formatOrder($order->load('orderDetails.productSku.product')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Đặt hàng thất bại. Vui lòng thử lại.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── DANH SÁCH ĐƠN HÀNG CỦA USER ────────────────────────────────────────
    // GET /api/orders
    public function index(Request $request)
    {
        $orders = Order::with(['orderDetails.productSku.product'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => $this->formatOrder($o));

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    // ─── CHI TIẾT ĐƠN HÀNG ───────────────────────────────────────────────────
    // GET /api/orders/{id}
    public function show(Request $request, $id)
    {
        $order = Order::with(['orderDetails.productSku.product'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatOrder($order),
        ]);
    }

    // ─── HUỶ ĐƠN HÀNG ────────────────────────────────────────────────────────
    // PATCH /api/orders/{id}/cancel
    public function cancel(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể huỷ đơn hàng đang chờ xác nhận.',
            ], 422);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Đơn hàng đã được huỷ thành công.',
            'data'    => $this->formatOrder($order),
        ]);
    }

    // ─── ADMIN: DANH SÁCH TẤT CẢ ĐƠN HÀNG ───────────────────────────────────
    // GET /api/admin/orders
    public function adminIndex(Request $request)
    {
        $orders = Order::with(['orderDetails.productSku.product', 'user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => $this->formatOrder($o));

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    // ─── ADMIN: CHI TIẾT ĐƠN HÀNG ────────────────────────────────────────────
    // GET /api/admin/orders/{id}
    public function adminShow($id)
    {
        $order = Order::with(['orderDetails.productSku.product', 'user'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatOrder($order),
        ]);
    }

    // ─── ADMIN: CẬP NHẬT TRẠNG THÁI ─────────────────────────────────────────
    // PATCH /api/admin/orders/{id}/status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipping,delivered,cancelled',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
            ], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công.',
            'data'    => $this->formatOrder($order),
        ]);
    }

    // ─── HELPER ──────────────────────────────────────────────────────────────
    private function formatOrder(Order $order): array
    {
        return [
            'id'            => $order->id,
            'email'         => $order->email,
            'phone'         => $order->phone,
            'address'       => $order->address,
            'total'         => $order->total,
            'payment'       => $order->payment,
            'status'        => $order->status,
            'created_at'    => $order->created_at,
            'order_details' => $order->orderDetails->map(fn($d) => [
                'id'               => $d->id,
                'product_sku_code' => $d->product_sku_code,
                'quantity'         => $d->quantity,
                'product'          => $d->productSku?->product ? [
                    'name'      => $d->productSku->product->name,
                    'image_url' => $d->productSku->product->image_url ?? null,
                    'price'     => $d->productSku->price ?? 0,
                ] : null,
            ])->values(),
        ];
    }
}