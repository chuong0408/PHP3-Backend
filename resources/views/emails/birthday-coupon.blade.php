<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chúc Mừng Sinh Nhật</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f4; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #ff6b6b, #feca57); padding: 50px 40px; text-align: center; }
        .header .emoji { font-size: 60px; display: block; margin-bottom: 15px; }
        .header h1 { color: #ffffff; font-size: 30px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.15); }
        .header p { color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 8px; }
        .body { padding: 40px; }
        .greeting { font-size: 18px; color: #333; margin-bottom: 20px; }
        .greeting strong { color: #ff6b6b; }
        .message { font-size: 15px; color: #555; line-height: 1.7; margin-bottom: 30px; }
        .coupon-box { background: linear-gradient(135deg, #fff5f5, #fff9e6); border: 2px dashed #ff6b6b; border-radius: 12px; padding: 30px; text-align: center; margin: 30px 0; }
        .coupon-box .label { font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; }
        .coupon-code { font-size: 32px; font-weight: 800; color: #ff6b6b; letter-spacing: 4px; background: #fff; padding: 12px 24px; border-radius: 8px; display: inline-block; margin: 10px 0; border: 1px solid #ffcdd2; }
        .coupon-discount { font-size: 20px; font-weight: 700; color: #333; margin-top: 10px; }
        .coupon-discount span { color: #ff6b6b; }
        .coupon-expires { font-size: 13px; color: #999; margin-top: 8px; }
        .cta { text-align: center; margin: 30px 0; }
        .cta a { background: linear-gradient(135deg, #ff6b6b, #ff8e53); color: white; padding: 15px 40px; border-radius: 50px; font-size: 16px; font-weight: 600; text-decoration: none; display: inline-block; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); }
        .note { background: #f8f9fa; border-left: 4px solid #feca57; padding: 15px 20px; border-radius: 0 8px 8px 0; font-size: 13px; color: #666; line-height: 1.6; margin: 20px 0; }
        .footer { background: #f8f9fa; text-align: center; padding: 25px 40px; border-top: 1px solid #eee; }
        .footer p { font-size: 13px; color: #999; line-height: 1.6; }
        .footer a { color: #ff6b6b; text-decoration: none; }
        .balloons { font-size: 24px; letter-spacing: 5px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <span class="emoji">🎂</span>
            <h1>Chúc Mừng Sinh Nhật!</h1>
            <p>Một ngày thật đặc biệt xứng đáng với một món quà đặc biệt</p>
        </div>

        <div class="body">
            <p class="greeting">Xin chào, <strong>{{ $user->fullname }}</strong>!</p>

            <p class="message">
                Hôm nay là ngày sinh nhật của bạn! 🎉 Toàn bộ đội ngũ chúng tôi muốn gửi lời chúc
                mừng sinh nhật nồng nhiệt nhất đến bạn. Nhân dịp đặc biệt này, chúng tôi có một
                món quà nhỏ muốn gửi tặng bạn — mã giảm giá sinh nhật độc quyền chỉ dành riêng cho bạn!
            </p>

            <div class="coupon-box">
                <p class="label">🎁 Mã Giảm Giá Sinh Nhật Của Bạn</p>
                <div class="coupon-code">{{ $couponCode }}</div>
                <p class="coupon-discount">Giảm <span>{{ $discount }}</span> cho đơn hàng tiếp theo</p>
                <p class="coupon-expires">⏰ Có hiệu lực đến: {{ $expiresAt }}</p>
            </div>

            <div class="cta">
                <a href="{{ config('app.url') }}">Mua Sắm Ngay →</a>
            </div>

            <div class="note">
                <strong>📋 Lưu ý khi sử dụng mã:</strong><br>
                • Mã chỉ có hiệu lực trong ngày sinh nhật của bạn và áp dụng cho 1 đơn hàng duy nhất.<br>
                • Không áp dụng đồng thời với các chương trình khuyến mãi khác.<br>
                • Nhập mã tại bước thanh toán để nhận ưu đãi.
            </div>
        </div>

        <div class="footer">
            <p class="balloons">🎈 🎊 🎉 🎁 🎂</p>
            <br>
            <p>Chúc bạn một ngày sinh nhật thật vui vẻ và hạnh phúc!</p>
            <p style="margin-top: 10px;">Trân trọng,<br><strong>Đội ngũ {{ config('app.name') }}</strong></p>
            <br>
            <p>Nếu bạn không muốn nhận email này, vui lòng <a href="#">hủy đăng ký</a>.</p>
        </div>
    </div>
</body>
</html>