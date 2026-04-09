<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\ShippingAddress;

class ProfileController extends Controller
{
    // ══════════════════════════════════════════════════════════
    // 1. XEM THÔNG TIN CÁ NHÂN
    // GET /api/profile
    // ══════════════════════════════════════════════════════════
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'address'  => $user->address,
                'birthday' => $user->birthday,
                'image'    => $user->image ? asset('storage/' . $user->image) : ($user->avatar ?? null),
                'provider' => $user->provider,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 2. CẬP NHẬT THÔNG TIN CÁ NHÂN (kể cả ảnh đại diện)
    // POST /api/profile
    // Body (multipart/form-data): fullname, phone, address, birthday, image(file)
    // ══════════════════════════════════════════════════════════
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'fullname' => 'sometimes|string|max:255',
            'phone'    => 'sometimes|string|max:20',
            'address'  => 'sometimes|string|max:255',
            'birthday' => 'sometimes|date',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Xử lý upload ảnh
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có (không phải ảnh Google OAuth)
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }
            $path = $request->file('image')->store('avatars', 'public');
            $user->image = $path;
        }

        // Chỉ cập nhật các field được gửi lên
        $fields = array_filter(
            $request->only(['fullname', 'phone', 'address', 'birthday']),
            fn($v) => $v !== null
        );
        $user->fill($fields);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công.',
            'data'    => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'address'  => $user->address,
                'birthday' => $user->birthday,
                'image'    => $user->image ? asset('storage/' . $user->image) : ($user->avatar ?? null),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 3. ĐỔI MẬT KHẨU
    // POST /api/profile/change-password
    // Body (JSON): current_password, new_password, new_password_confirmation
    // ══════════════════════════════════════════════════════════
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:8|confirmed',
            // 'new_password_confirmation' phải khớp với 'new_password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Tài khoản Google OAuth không có mật khẩu riêng
        if ($user->provider === 'google' && empty($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản đăng nhập bằng Google không thể đổi mật khẩu tại đây.',
            ], 400);
        }

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng.',
            ], 400);
        }

        // Không cho đặt mật khẩu mới trùng mật khẩu cũ
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu mới không được trùng mật khẩu cũ.',
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 4. DANH SÁCH ĐỊA CHỈ GIAO HÀNG
    // GET /api/profile/addresses
    // ══════════════════════════════════════════════════════════
    public function listAddresses(Request $request)
    {
        $addresses = ShippingAddress::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $addresses,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 5. THÊM ĐỊA CHỈ GIAO HÀNG MỚI
    // POST /api/profile/addresses
    // Body (JSON): receiver_name, phone, province, district, ward, detail_address, is_default(bool)
    // ══════════════════════════════════════════════════════════
    public function storeAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_name'  => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'province'       => 'required|string|max:100',
            'district'       => 'required|string|max:100',
            'ward'           => 'required|string|max:100',
            'detail_address' => 'required|string|max:255',
            'is_default'     => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId = $request->user()->id;

        // Nếu đặt làm mặc định, bỏ mặc định của địa chỉ cũ
        if ($request->boolean('is_default')) {
            ShippingAddress::where('user_id', $userId)->update(['is_default' => false]);
        }

        // Nếu chưa có địa chỉ nào → tự động đặt làm mặc định
        $count = ShippingAddress::where('user_id', $userId)->count();
        $isDefault = $request->boolean('is_default') || $count === 0;

        $address = ShippingAddress::create([
            'user_id'        => $userId,
            'receiver_name'  => $request->receiver_name,
            'phone'          => $request->phone,
            'province'       => $request->province,
            'district'       => $request->district,
            'ward'           => $request->ward,
            'detail_address' => $request->detail_address,
            'is_default'     => $isDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm địa chỉ thành công.',
            'data'    => $address,
        ], 201);
    }

    // ══════════════════════════════════════════════════════════
    // 6. CẬP NHẬT ĐỊA CHỈ GIAO HÀNG
    // PUT /api/profile/addresses/{id}
    // ══════════════════════════════════════════════════════════
    public function updateAddress(Request $request, $id)
    {
        $userId  = $request->user()->id;
        $address = ShippingAddress::where('id', $id)->where('user_id', $userId)->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Địa chỉ không tồn tại.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'receiver_name'  => 'sometimes|string|max:255',
            'phone'          => 'sometimes|string|max:20',
            'province'       => 'sometimes|string|max:100',
            'district'       => 'sometimes|string|max:100',
            'ward'           => 'sometimes|string|max:100',
            'detail_address' => 'sometimes|string|max:255',
            'is_default'     => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_default')) {
            ShippingAddress::where('user_id', $userId)->update(['is_default' => false]);
        }

        $address->fill($request->only([
            'receiver_name',
            'phone',
            'province',
            'district',
            'ward',
            'detail_address',
            'is_default',
        ]));
        $address->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công.',
            'data'    => $address,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 7. XÓA ĐỊA CHỈ GIAO HÀNG
    // DELETE /api/profile/addresses/{id}
    // ══════════════════════════════════════════════════════════
    public function deleteAddress(Request $request, $id)
    {
        $userId  = $request->user()->id;
        $address = ShippingAddress::where('id', $id)->where('user_id', $userId)->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Địa chỉ không tồn tại.',
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Nếu xóa địa chỉ mặc định → tự động đặt địa chỉ mới nhất làm mặc định
        if ($wasDefault) {
            $next = ShippingAddress::where('user_id', $userId)->orderByDesc('created_at')->first();
            if ($next) {
                $next->is_default = true;
                $next->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Xóa địa chỉ thành công.',
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 8. ĐẶT LÀM ĐỊA CHỈ MẶC ĐỊNH
    // PATCH /api/profile/addresses/{id}/set-default
    // ══════════════════════════════════════════════════════════
    public function setDefaultAddress(Request $request, $id)
    {
        $userId  = $request->user()->id;
        $address = ShippingAddress::where('id', $id)->where('user_id', $userId)->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Địa chỉ không tồn tại.',
            ], 404);
        }

        // Bỏ tất cả mặc định → đặt lại
        ShippingAddress::where('user_id', $userId)->update(['is_default' => false]);
        $address->is_default = true;
        $address->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt làm địa chỉ mặc định.',
            'data'    => $address,
        ]);
    }
}
