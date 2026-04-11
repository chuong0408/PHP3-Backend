<?php

namespace App\Http\Controllers;

use App\Mail\RegisterSuccessMail;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ─── ĐĂNG KÝ ─────────────────────────────────────────────────────────────
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:user',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()   // có cả chữ hoa lẫn chữ thường
                    ->numbers()     // có ít nhất 1 chữ số
                    ->symbols(),    // có ít nhất 1 ký tự đặc biệt
            ],
        ], [
            'fullname.required'  => 'Vui lòng nhập họ tên.',
            'email.required'     => 'Vui lòng nhập email.',
            'email.email'        => 'Email không hợp lệ.',
            'email.unique'       => 'Email này đã được sử dụng.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'fullname' => $request->fullname,
            'phone'    => $request->phone ?? '',
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 0, // 0 = user, 1 = admin
            'provider' => 'local',
        ]);

        // Gửi email đăng ký thành công
        try {
            Mail::to($user->email)->send(new RegisterSuccessMail($user));
        } catch (\Exception $e) {
            // Không dừng luồng nếu gửi mail thất bại
            Log::warning('Không thể gửi email đăng ký: ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công! Chúng tôi đã gửi email xác nhận đến ' . $user->email,
            'data'    => [
                'user'  => $this->formatUser($user),
                'token' => $token,
            ],
        ], 201);
    }

    // ─── ĐĂNG NHẬP ───────────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Vui lòng nhập email.',
            'email.email'       => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email không tồn tại trong hệ thống.',
            ], 401);
        }

        // Tài khoản Google không có password
        if ($user->provider === 'google' && empty($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản này được đăng ký bằng Google. Vui lòng đăng nhập bằng Google.',
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email Hoặc mật khẩu không chính xác.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công!',
            'data'    => [
                'user'  => $this->formatUser($user),
                'token' => $token,
            ],
        ]);
    }

    // ─── ĐĂNG XUẤT ───────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đăng xuất thành công.']);
        
    }

    // LẤY THÔNG TIN USER HIỆN TẠI
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($request->user()),
        ]);
    }

    // =========================================================================
    // GOOGLE OAUTH
    // =========================================================================

    /**
     * Bước 1: Chuyển hướng người dùng đến Google để xác thực.
     */
    public function redirectToGoogle()
    {
        try {
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect');

            if (!$clientId || !$clientSecret || !$redirectUri) {
                Log::error('Google OAuth config missing', compact('clientId', 'clientSecret', 'redirectUri'));
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu cấu hình Google OAuth. Vui lòng kiểm tra GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI.',
                ], 500);
            }

            /** @phpstan-ignore-next-line */
            /** @psalm-suppress UndefinedMethod */
            /** @noinspection PhpUndefinedMethodInspection */
            $url = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'success' => true,
                'url'     => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('Google redirect failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Không thể kết nối tới Google. Vui lòng kiểm tra cấu hình GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI.',
            ], 500);
        }
    }

    /**
     * Bước 2: Google callback — tạo/cập nhật user và trả về token.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            /** @phpstan-ignore-next-line */
            /** @psalm-suppress UndefinedMethod */
            /** @noinspection PhpUndefinedMethodInspection */
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google callback failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->redirectToFrontendWithError('Xác thực Google thất bại. Vui lòng thử lại.');
        }

        // Tìm user theo google_id hoặc email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $isNewUser = false;

        if ($user) {
            // Cập nhật thông tin Google nếu chưa có
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                    'provider'  => 'google',
                ]);
            }
        } else {
            // Tạo user mới từ Google
            $isNewUser = true;
            $user = User::create([
                'fullname'  => $googleUser->getName(),
                'phone'     => '',  
                'email'     => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
                'password'  => Hash::make(Str::random(32)), // random password
                'provider'  => 'google',
                'role'      => 0, // 0 = user, 1 = admin
            ]);

            // Gửi email chào mừng khi đăng ký qua Google
            try {
                Mail::to($user->email)->send(new RegisterSuccessMail($user));
            } catch (\Exception $e) {
                Log::warning('Không thể gửi email chào mừng Google: ' . $e->getMessage());
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Redirect về frontend kèm token
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $redirectUrl = $frontendUrl . '/auth/google/callback'
            . '?token=' . urlencode($token)
            . '&user=' . urlencode(json_encode($this->formatUser($user)))
            . '&is_new=' . ($isNewUser ? '1' : '0');

        return redirect($redirectUrl);
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
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự.',
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

    private function redirectToFrontendWithError(string $message): \Illuminate\Http\RedirectResponse
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        return redirect($frontendUrl . '/login?error=' . urlencode($message));
    }
}
