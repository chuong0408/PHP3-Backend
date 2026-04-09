<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSku;
use App\Mail\OrderSuccessMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VNPayController extends Controller
{
    /**
     * POST /api/vnpay/create-payment
     * Tạo đơn hàng + sinh URL redirect sang VNPay.
     * Yêu cầu đăng nhập (auth:sanctum).
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'email'   => 'required|email|max:255',
            'phone'   => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'items'   => 'required|array|min:1',
            'items.*.product_sku_code' => 'required|string|exists:product_skus,sku_code',
            'items.*.quantity'         => 'required|integer|min:1',
            'coupon_code' => 'nullable|string',
        ]);

        $user       = $request->user();
        $total      = 0;
        $skuObjects = [];

        foreach ($request->items as $item) {
            $sku = ProductSku::where('sku_code', $item['product_sku_code'])
                ->where('status', 'active')->first();
            if (!$sku) {
                return response()->json(['message' => "SKU [{$item['product_sku_code']}] không hợp lệ."], 422);
            }
            if ($sku->quantity < $item['quantity']) {
                return response()->json(['message' => "SKU [{$sku->sku_code}] chỉ còn {$sku->quantity} trong kho."], 422);
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

        $shippingFee = $total >= 5_000_000 ? 0 : 30_000;
        $total      += $shippingFee;

        // Tạo đơn hàng trạng thái pending_payment (chờ VNPay xác nhận)
        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id'    => $user->id,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'address'    => $request->address,
                'total'      => $total,
                'payment'    => 'vnpay',
                'status'     => 'pending_payment',
                'created_at' => now(),
                'coupon_code' => $request->coupon_code ? strtoupper(trim($request->coupon_code)) : null,
                'discount'    => $discount,
            ]);
            foreach ($skuObjects as $entry) {
                OrderDetail::create([
                    'orders_id'        => $order->id,
                    'product_sku_code' => $entry['sku']->sku_code,
                    'quantity'         => $entry['quantity'],
                ]);
                // Giữ kho, chỉ trừ sau khi VNPay xác nhận thành công
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Tạo đơn hàng thất bại: ' . $e->getMessage()], 500);
        }

        // Sinh URL VNPay
        $vnpayUrl = $this->buildVNPayUrl($order->id, (int) $total);

        return response()->json([
            'order_id'    => $order->id,
            'payment_url' => $vnpayUrl,
        ]);
    }

    /**
     * GET /api/vnpay/callback
     * VNPay redirect về đây sau khi user thanh toán.
     * Xác minh chữ ký → cập nhật đơn hàng → redirect về frontend.
     */
    public function callback(Request $request)
    {
        $vnpParams = $request->query();
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        // Xác minh secure hash
        if (!$this->verifyHash($vnpParams)) {
            Log::warning('VNPay callback: invalid hash', $vnpParams);
            return redirect($frontendUrl . '/checkout?vnpay=fail&reason=invalid_hash');
        }

        $orderId    = (int) ($vnpParams['vnp_TxnRef'] ?? 0);
        $responseCode = $vnpParams['vnp_ResponseCode'] ?? '';
        $order      = Order::with('details.sku')->find($orderId);

        if (!$order) {
            return redirect($frontendUrl . '/checkout?vnpay=fail&reason=not_found');
        }

        if ($responseCode === '00') {
            // Thanh toán thành công
            DB::transaction(function () use ($order) {
                $order->status = 'pending'; // Chờ xử lý bình thường
                $order->save();

                // Trừ tồn kho
                foreach ($order->details as $detail) {
                    $sku = ProductSku::where('sku_code', $detail->product_sku_code)->first();
                    if ($sku) $sku->decrement('quantity', $detail->quantity);
                }
                if ($order->coupon_code) {
                    \App\Models\CouponUsage::where('user_id', $order->user_id)
                        ->where('coupon_code', $order->coupon_code)
                        ->whereNull('used_at')
                        ->update(['used_at' => now()]);
                }
            });

            // Gửi email xác nhận
            try {
                $order->load('details.sku.product');
                $items = $order->details->map(fn($d) => [
                    'product_name'     => $d->sku?->product?->name,
                    'product_sku_code' => $d->product_sku_code,
                    'quantity'         => $d->quantity,
                    'price'            => (float) ($d->sku?->price ?? 0),
                    'product_image'    => null,
                ])->toArray();

                $subtotal    = collect($items)->sum(fn($i) => $i['price'] * $i['quantity']);
                $shippingFee = $subtotal >= 5_000_000 ? 0 : 30_000;

                Mail::to($order->email)->send(new OrderSuccessMail(
                    order: $order,
                    user: $order->user,
                    items: $items,
                    subtotal: $subtotal,
                    shippingFee: $shippingFee,
                ));
            } catch (\Throwable $e) {
                Log::warning('VNPay: không gửi được email: ' . $e->getMessage());
            }

            return redirect($frontendUrl . '/checkout?vnpay=success&order_id=' . $order->id);
        } else {
            // Thanh toán thất bại / huỷ
            $order->update(['status' => 'cancelled']);

            // Không cần hoàn kho vì chưa trừ
            return redirect($frontendUrl . '/checkout?vnpay=fail&reason=payment_failed&order_id=' . $order->id);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildVNPayUrl(int $orderId, int $amount): string
    {
        $vnpUrl        = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        $vnpReturnUrl  = env('APP_URL', 'http://localhost:8000') . '/api/vnpay/callback';
        $vnpTmnCode    = env('VNP_TMN_CODE', 'SANDBOX');
        $vnpHashSecret = env('VNP_HASH_SECRET', '');

        $params = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $vnpTmnCode,
            'vnp_Amount'     => $amount * 100,         // VNPay tính theo đồng × 100
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $orderId,
            'vnp_OrderInfo'  => 'Thanh toan don hang #' . $orderId,
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => 'vn',
            'vnp_ReturnUrl'  => $vnpReturnUrl,
            'vnp_IpAddr'     => request()->ip(),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => now()->addMinutes(15)->format('YmdHis'),
        ];

        ksort($params);
        $query     = http_build_query($params);
        $hmac      = hash_hmac('sha512', $query, $vnpHashSecret);
        $params['vnp_SecureHash'] = $hmac;

        return $vnpUrl . '?' . http_build_query($params);
    }

    private function verifyHash(array $params): bool
    {
        $vnpHashSecret = env('VNP_HASH_SECRET', '');
        $secureHash    = $params['vnp_SecureHash'] ?? '';

        $checkParams = collect($params)
            ->filter(fn($v, $k) => str_starts_with($k, 'vnp_') && $k !== 'vnp_SecureHash')
            ->sortKeys()
            ->toArray();

        $hashData = http_build_query($checkParams);
        $expected = hash_hmac('sha512', $hashData, $vnpHashSecret);

        return hash_equals($expected, $secureHash);
    }
}
