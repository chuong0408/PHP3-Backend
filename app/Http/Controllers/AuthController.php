<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // ─── ĐĂNG KÝ ─────────────────────────────────────────────────────────────
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email|unique:user,email',
            'phone'    => 'required|string|max:50',
            'password' => 'required|string|min:6',
            'address'  => 'nullable|string|max:255',
        ], [
            'email.unique'    => 'Email này đã được sử dụng.',
            'email.required'  => 'Email không được để trống.',
            'password.min'    => 'Mật khẩu tối thiểu 6 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::create([
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'address'  => $request->address ?? '',
            'password' => Hash::make($request->password),
            'role'     => 0,
            'status'   => 1,
            'brithday' => now(),
            'image'    => '',
            'otp'      => '',
            'otp_time' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng ký thành công!',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ], 201);
    }

    // ─── ĐĂNG NHẬP ───────────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        if ($user->status == 0) {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa.',
            ], 403);
        }

        // Xóa token cũ (optional - 1 session tại một thời điểm)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    // ─── ĐĂNG XUẤT ───────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }

    // ─── QUÊN MẬT KHẨU: GỬI OTP ─────────────────────────────────────────────
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:user,email',
        ], [
            'email.exists' => 'Email không tồn tại trong hệ thống.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Tạo OTP 6 số
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Lưu OTP và thời gian hết hạn (5 phút)
        $user->otp      = $otp;
        $user->otp_time = now()->addMinutes(5);
        $user->save();

        // Gửi mail OTP
        Mail::to($user->email)->send(new OtpMail($user->fullname, $otp));

        return response()->json([
            'message' => 'Mã OTP đã được gửi đến email của bạn. Có hiệu lực trong 5 phút.',
        ]);
    }

    // ─── XÁC MINH OTP ────────────────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:user,email',
            'otp'   => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->otp !== $request->otp) {
            return response()->json([
                'message' => 'Mã OTP không chính xác.',
            ], 422);
        }

        if (now()->isAfter($user->otp_time)) {
            return response()->json([
                'message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.',
            ], 422);
        }

        return response()->json([
            'message' => 'Xác minh OTP thành công.',
        ]);
    }

    // ─── ĐẶT LẠI MẬT KHẨU ───────────────────────────────────────────────────
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:user,email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min'       => 'Mật khẩu tối thiểu 6 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Kiểm tra lại OTP lần cuối
        if ($user->otp !== $request->otp) {
            return response()->json([
                'message' => 'Phiên xác minh không hợp lệ. Vui lòng thử lại.',
            ], 422);
        }

        if (now()->isAfter($user->otp_time)) {
            return response()->json([
                'message' => 'Phiên xác minh đã hết hạn. Vui lòng yêu cầu OTP mới.',
            ], 422);
        }

        // Cập nhật mật khẩu và xóa OTP
        $user->password = Hash::make($request->password);
        $user->otp      = '';
        $user->otp_time = now();
        $user->save();

        return response()->json([
            'message' => 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập.',
        ]);
    }

    // ─── HELPER ──────────────────────────────────────────────────────────────
    private function formatUser(User $user): array
    {
        return [
            'id'       => $user->id,
            'fullname' => $user->fullname,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'address'  => $user->address,
            'image'    => $user->image,
            'role'     => $user->role,
            'status'   => $user->status,
        ];
    }
}