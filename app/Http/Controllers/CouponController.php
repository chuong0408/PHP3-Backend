<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    // GET /admin/coupons — danh sách tất cả mã
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('coupon_code', 'like', "%$s%")
                    ->orWhere('description', 'like', "%$s%");
            });
        }

        $coupons = $query->orderByDesc('created_at')->get();

        return response()->json($coupons);
    }

    // POST /admin/coupons — tạo mã mới
    public function store(Request $request)
    {
        $request->validate([
            'coupon_code'   => 'required|string|max:255|unique:coupon_details,coupon_code',
            'discount'      => 'required|string|max:10',
            'description'   => 'required|string|max:255',
            'minordervalue' => 'nullable|numeric|min:0',
            'expires_at'    => 'nullable|date|after:now',
        ], [
            'coupon_code.unique' => 'Mã giảm giá này đã tồn tại.',
            'expires_at.after'   => 'Ngày hết hạn phải sau thời điểm hiện tại.',
        ]);

        $coupon = Coupon::create([
            'coupon_code'   => strtoupper(trim($request->coupon_code)),
            'discount'      => $request->discount,
            'description'   => $request->description,
            'minordervalue' => $request->minordervalue ?? 0,
            'expires_at'    => $request->expires_at ?? null,
        ]);

        return response()->json($coupon, 201);
    }

    // GET /admin/coupons/{coupon} — chi tiết 1 mã
    public function show(string $coupon_code)
    {
        $coupon = Coupon::with('usages.user')->findOrFail($coupon_code);
        return response()->json($coupon);
    }

    // PUT /admin/coupons/{coupon} — cập nhật mã
    public function update(Request $request, string $coupon_code)
    {
        $coupon = Coupon::findOrFail($coupon_code);

        $request->validate([
            'discount'      => 'required|string|max:10',
            'description'   => 'required|string|max:255',
            'minordervalue' => 'nullable|numeric|min:0',
            'expires_at'    => 'nullable|date',
        ]);

        $coupon->update([
            'discount'      => $request->discount,
            'description'   => $request->description,
            'minordervalue' => $request->minordervalue ?? 0,
            'expires_at'    => $request->expires_at ?? null,
        ]);

        return response()->json($coupon->fresh());
    }

    // DELETE /admin/coupons/{coupon} — xóa mã (cascade xóa luôn bảng coupon)
    public function destroy(string $coupon_code)
    {
        $coupon = Coupon::findOrFail($coupon_code);
        $coupon->delete(); // cascade sẽ xóa records trong bảng coupon
        return response()->json(['message' => 'Xóa mã giảm giá thành công!']);
    }

    // POST /apply-coupon — user áp dụng mã lúc checkout
    public function apply(Request $request)
    {
        $request->validate([
            'code'        => 'required|string',
            'order_total' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('coupon_code', strtoupper(trim($request->code)))->first();

        if (!$coupon) {
            return response()->json(['message' => 'Mã giảm giá không tồn tại.'], 422);
        }

        if ($coupon->isExpired()) {
            return response()->json(['message' => 'Mã giảm giá đã hết hạn.'], 422);
        }

        if ($request->order_total < $coupon->minordervalue) {
            return response()->json([
                'message' => 'Đơn hàng tối thiểu ' . number_format($coupon->minordervalue) . '₫ để dùng mã này.',
            ], 422);
        }

        // Kiểm tra user đã dùng chưa (nếu đã đăng nhập)
        if (Auth::check() && $coupon->isUsedByUser(Auth::id())) {
            return response()->json(['message' => 'Bạn đã sử dụng mã giảm giá này rồi.'], 422);
        }

        // Parse discount: "20%" hoặc "50000"
        $discountStr = $coupon->discount;
        if (str_ends_with($discountStr, '%')) {
            $percent  = floatval($discountStr);
            $discount = $request->order_total * $percent / 100;
        } else {
            $discount = floatval($discountStr);
        }

        $discount = min($discount, $request->order_total);

        return response()->json([
            'coupon'   => $coupon,
            'discount' => round($discount, 2),
            'final'    => round($request->order_total - $discount, 2),
        ]);
    }

    // POST /admin/coupons/{coupon}/use — ghi nhận user đã dùng mã
    public function markUsed(Request $request, string $coupon_code)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:user,id',
        ]);

        Coupon::findOrFail($coupon_code);

        $already = CouponUsage::where('coupon_code', $coupon_code)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($already) {
            return response()->json(['message' => 'User này đã dùng mã rồi.'], 422);
        }

        $usage = CouponUsage::create([
            'user_id'     => $request->user_id,
            'coupon_code' => $coupon_code,
        ]);

        return response()->json($usage, 201);
    }
}
