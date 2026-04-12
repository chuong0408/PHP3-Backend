<?php

namespace App\Http\Controllers;

use App\Mail\OrderSuccessMail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\GhnService;

class OrderController extends Controller
{
    /**
     * POST /api/user/orders
     * Đặt hàng COD (hoặc phương thức khác).
     * Yêu cầu đăng nhập (auth:sanctum).
     */
    public function store(Request $request)
    {
        $request->validate([
            'email'            => 'required|email|max:255',
            'phone'            => 'required|string|max:20',
            'address'          => 'required|string|max:500',

            'to_district_id'   => 'required|integer',
            'to_ward_code'     => 'required|string',
            'weight'           => 'nullable|integer|min:1',

            'payment'          => 'required|string|in:cod,banking,momo,vnpay',
            'items'            => 'required|array|min:1',
            'items.*.product_sku_code' => 'required|string|exists:product_skus,sku_code',
            'items.*.quantity'         => 'required|integer|min:1',
            'coupon_code' => 'nullable|string',
        ], [
            'email.required'   => 'Vui lòng nhập email.',
            'phone.required'   => 'Vui lòng nhập số điện thoại.',
            'address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'payment.in'       => 'Phương thức thanh toán không hợp lệ.',
            'items.required'   => 'Giỏ hàng trống.',
            'items.*.product_sku_code.exists' => 'Sản phẩm không tồn tại.',
            'items.*.quantity.min'            => 'Số lượng phải ít nhất là 1.',
        ]);

        $user = $request->user();

        // Tính tổng tiền và kiểm tra tồn kho
        $total      = 0;
        $skuObjects = [];

        foreach ($request->items as $item) {
            $sku = ProductSku::where('sku_code', $item['product_sku_code'])
                ->where('status', 'active')
                ->first();

            if (! $sku) {
                return response()->json([
                    'message' => "Sản phẩm SKU [{$item['product_sku_code']}] không còn hoạt động.",
                ], 422);
            }

            if ($sku->quantity < $item['quantity']) {
                return response()->json([
                    'message' => "Sản phẩm [{$sku->sku_code}] chỉ còn {$sku->quantity} trong kho.",
                ], 422);
            }

            $total += (float) $sku->price * (int) $item['quantity'];
            $skuObjects[] = ['sku' => $sku, 'quantity' => (int) $item['quantity']];
        }

        $discount = 0;
        if ($request->filled('coupon_code')) {
            $coupon = \App\Models\Coupon::where('coupon_code', strtoupper(trim($request->coupon_code)))->first();
            if ($coupon && !$coupon->isExpired()) {
                $d = $coupon->discount;
                if (str_ends_with($d, '%')) {
                    $discount = $total * floatval($d) / 100;
                } else {
                    $discount = floatval($d);
                }
                $discount = min($discount, $total);
                $total   -= $discount;
            }
        }

        $ghn = new GhnService();
        $shippingFee = $ghn->calculateFee(
            toDistrictId: (int) $request->to_district_id,
            toWardCode: $request->to_ward_code,
            weight: (int) ($request->weight ?? 500),
            insuranceValue: (int) $total,
        ) ?? 30_000; 

        $total += $shippingFee;

        // Tạo đơn hàng trong transaction
        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id'    => $user->id,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'address'    => $request->address,
                'total'      => $total,
                'payment'    => $request->payment,
                'status'     => 'pending',
                'created_at' => now(),
                'coupon_code' => $request->coupon_code ? strtoupper(trim($request->coupon_code)) : null,
                'discount'    => $discount,
                'shipping_fee' => $shippingFee,
            ]);

            foreach ($skuObjects as $entry) {
                OrderDetail::create([
                    'orders_id'        => $order->id,
                    'product_sku_code' => $entry['sku']->sku_code,
                    'quantity'         => $entry['quantity'],
                ]);

                // Trừ tồn kho
                $entry['sku']->decrement('quantity', $entry['quantity']);
            }

            DB::commit();
            if ($request->filled('coupon_code') && $discount > 0) {
                \App\Models\CouponUsage::where('user_id', $user->id)
                    ->where('coupon_code', strtoupper(trim($request->coupon_code)))
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Đặt hàng thất bại, vui lòng thử lại. ' . $e->getMessage(),
            ], 500);
        }

        // Load order với details để format và gửi email
        $order->load('details.sku.product');
        $formatted = $this->formatOrder($order);

        // Tính subtotal & shippingFee để hiển thị trong email
        $subtotal    = collect($formatted['items'])->sum(fn($i) => ($i['price'] ?? 0) * $i['quantity']);
        $shippingFee = $subtotal >= 5_000_000 ? 0 : 30_000;

        // Gửi email xác nhận đặt hàng (không chặn response nếu lỗi mail)
        try {
            Mail::to($user->email)->send(new OrderSuccessMail(
                order: $order,
                user: $user,
                items: $formatted['items'],
                subtotal: $subtotal,
                shippingFee: $shippingFee,
            ));
        } catch (\Throwable $mailErr) {
            // Log lỗi mail nhưng không fail request
            Log::warning('Không thể gửi email đặt hàng: ' . $mailErr->getMessage());
        }

        return response()->json([
            'message' => 'Đặt hàng thành công!',
            'order'   => $formatted,
        ], 201);
    }

    /**
     * GET /api/user/orders
     * Lấy lịch sử đơn hàng của user đang đăng nhập.
     */
    public function index(Request $request)
    {
        $user   = $request->user();
        $orders = Order::with(['details.sku.product'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $orders->getCollection()->transform(fn($o) => $this->formatOrder($o));

        return response()->json($orders);
    }

    /**
     * GET /api/user/orders/{id}
     * Chi tiết 1 đơn hàng (chỉ của user đó).
     */
    public function show(Request $request, $id)
    {
        $user  = $request->user();
        $order = Order::with(['details.sku.product'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json($this->formatOrder($order));
    }

    /**
     * PATCH /api/user/orders/{id}/cancel
     * Huỷ đơn hàng (chỉ khi status = pending).
     */
    public function cancel(Request $request, $id)
    {
        $user  = $request->user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Chỉ có thể huỷ đơn hàng đang chờ xác nhận.',
            ], 422);
        }

        // Hoàn trả tồn kho
        foreach ($order->details as $detail) {
            $sku = ProductSku::where('sku_code', $detail->product_sku_code)->first();
            if ($sku) {
                $sku->increment('quantity', $detail->quantity);
            }
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'message' => 'Huỷ đơn hàng thành công.',
            'order'   => $this->formatOrder($order->load('details.sku')),
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
        })->toArray();

        return [
            'id'         => $order->id,
            'email'      => $order->email,
            'phone'      => $order->phone,
            'address'    => $order->address,
            'total'      => $order->total,
            'payment'    => $order->payment,
            'status'     => $order->status,
            'created_at' => $order->created_at?->toDateTimeString(),
            'items'      => $items,
        ];
    }
}
