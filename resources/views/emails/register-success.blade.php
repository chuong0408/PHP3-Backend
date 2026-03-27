<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký thành công</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .header p {
            color: rgba(255,255,255,0.85);
            font-size: 15px;
        }
        .checkmark {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }
        .body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
        }
        .text {
            color: #718096;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .info-box {
            background: #f7f8fc;
            border-left: 4px solid #667eea;
            border-radius: 6px;
            padding: 20px 24px;
            margin: 24px 0;
        }
        .info-box p {
            color: #4a5568;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .info-box strong {
            color: #2d3748;
        }
        .btn-wrapper {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 30px 0;
        }
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin: 24px 0;
        }
        .feature-item {
            flex: 1;
            min-width: 150px;
            background: #f7f8fc;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .feature-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .feature-text {
            font-size: 13px;
            color: #718096;
        }
        .footer {
            background: #f7f8fc;
            padding: 24px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            color: #a0aec0;
            font-size: 13px;
            line-height: 1.6;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <div class="header">
            <div class="checkmark">✅</div>
            <h1>Đăng ký thành công!</h1>
            <p>Chào mừng bạn đến với {{ $appName }}</p>
        </div>

        <!-- Body -->
        <div class="body">
            <p class="greeting">Xin chào {{ $user->fullname }},</p>

            <p class="text">
                Cảm ơn bạn đã tạo tài khoản tại <strong>{{ $appName }}</strong>.
                Tài khoản của bạn đã được kích hoạt và sẵn sàng sử dụng!
            </p>

            <!-- Thông tin tài khoản -->
            <div class="info-box">
                <p>📧 <strong>Email:</strong> {{ $user->email }}</p>
                <p>👤 <strong>Họ tên: {{ $user->fullname }}</p>
                <p>🗓️ <strong>Ngày đăng ký:</strong>{{ now()->format('d/m/Y H:i') }}</p>
                @if($user->provider === 'google')
                <p>🔗 <strong>Đăng nhập qua:</strong> Google</p>
                @endif
            </div>

            <p class="text">
                Bạn có thể bắt đầu khám phá các sản phẩm và tính năng của chúng tôi ngay bây giờ.
            </p>

            <!-- Features -->
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">🛍️</div>
                    <div class="feature-text">Mua sắm dễ dàng</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🚚</div>
                    <div class="feature-text">Giao hàng nhanh chóng</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔒</div>
                    <div class="feature-text">Bảo mật an toàn</div>
                </div>
            </div>

            <div class="btn-wrapper">
                <a href="{{ $appUrl }}" class="btn">Bắt đầu mua sắm →</a>
            </div>

            <hr class="divider">

            <p class="text" style="font-size: 13px;">
                Nếu bạn không thực hiện đăng ký này, vui lòng bỏ qua email này
                hoặc liên hệ với chúng tôi ngay lập tức.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                Email này được gửi tự động từ <strong>{{ $appName }}</strong>.<br>
                Vui lòng không trả lời email này.
            </p>
            <p style="margin-top: 8px;">
                <a href="{{ $appUrl }}">Trang chủ</a> ·
                <a href="{{ $appUrl }}/profile">Hồ sơ</a>
            </p>
        </div>
    </div>
</body>
</html>