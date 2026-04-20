<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductSku;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * GET /api/admin/orders
     * Danh sách tất cả đơn hàng (có lọc, phân trang).
     */
    public function index(Request $request)
    {
        $query = Order::with(['details.sku.product', 'user'])
            ->orderByDesc('created_at');

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo phương thức thanh toán
        if ($request->filled('payment')) {
            $query->where('payment', $request->payment);
        }

        // Tìm kiếm theo email hoặc số điện thoại
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $orders = $query->paginate(15);

        $orders->getCollection()->transform(fn($o) => $this->formatOrder($o));

        return response()->json($orders);
    }

    /**
     * GET /api/admin/orders/{id}
     * Chi tiết 1 đơn hàng.
     */
    public function show($id)
    {
        $order = Order::with(['details.sku.product', 'user'])->findOrFail($id);

        return response()->json($this->formatOrder($order));
    }

    /**
     * PATCH /api/admin/orders/{id}/status
     * Cập nhật trạng thái đơn hàng.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,shipping,delivered,cancelled',
        ], [
            'status.in' => 'Trạng thái không hợp lệ.',
        ]);

        $order = Order::with('details')->findOrFail($id);

        $old = $order->status;
        $new = $request->status;

        // Hoàn trả tồn kho nếu chuyển sang cancelled từ trạng thái chưa cancelled
        if ($new === 'cancelled' && $old !== 'cancelled') {
            foreach ($order->details as $detail) {
                $sku = ProductSku::where('sku_code', $detail->product_sku_code)->first();
                if ($sku) {
                    $sku->increment('quantity', $detail->quantity);
                }
            }
        }

        // Trừ lại tồn kho nếu khôi phục từ cancelled sang trạng thái khác
        if ($old === 'cancelled' && $new !== 'cancelled') {
            foreach ($order->details as $detail) {
                $sku = ProductSku::where('sku_code', $detail->product_sku_code)->first();
                if ($sku) {
                    if ($sku->quantity < $detail->quantity) {
                        return response()->json([
                            'message' => "SKU [{$sku->sku_code}] không đủ tồn kho để khôi phục đơn hàng.",
                        ], 422);
                    }
                    $sku->decrement('quantity', $detail->quantity);
                }
            }
        }

        $order->status = $new;
        $order->save();

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công.',
            'order'   => $this->formatOrder($order->load('details.sku.product', 'user')),
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function formatOrder(Order $order): array
    {
        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

        $items = $order->details->map(function ($detail) use ($base) {
            $sku     = $detail->sku;
            $product = $sku?->product;

            $imageUrl = null;
            if ($product?->image_url) {
                $imageUrl = str_starts_with($product->image_url, 'http')
                    ? $product->image_url
                    : $base . '/storage/' . $product->image_url;
            }

            return [
                'id'               => $detail->id,
                'product_sku_code' => $detail->product_sku_code,
                'quantity'         => $detail->quantity,
                'price'            => $sku ? (float) $sku->price : null,
                'product_name'     => $product?->name,
                'product_image'    => $imageUrl,
            ];
        });

        return [
            'id'            => $order->id,
            'email'         => $order->email,
            'phone'         => $order->phone,
            'address'       => $order->address,
            'total'         => (float) $order->total,
            'payment'       => $order->payment,
            'status'        => $order->status,
            'cancel_reason' => $order->cancel_reason,
            'created_at'    => $order->created_at?->toDateTimeString(),
            'items'         => $items,
            'user'          => $order->user ? [
                'id'       => $order->user->id,
                'fullname' => $order->user->fullname,
                'email'    => $order->user->email,
            ] : null,
        ];
    }
}