<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mã OTP GreenElectric</title>
  <style>
    body { margin: 0; padding: 0; background: #f0f4f0; font-family: 'Segoe UI', Arial, sans-serif; }
    .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(46,125,50,0.10); }
    .header { background: linear-gradient(135deg, #2e7d32, #43a047); padding: 36px 40px 28px; text-align: center; }
    .header h1 { margin: 0; color: #fff; font-size: 24px; font-weight: 700; letter-spacing: 1px; }
    .header p  { margin: 6px 0 0; color: rgba(255,255,255,0.85); font-size: 14px; }
    .body { padding: 36px 40px; }
    .greeting { font-size: 16px; color: #333; margin-bottom: 16px; }
    .info     { font-size: 14px; color: #555; line-height: 1.7; margin-bottom: 24px; }
    .otp-box  {
      background: #f1f8e9;
      border: 2px dashed #66bb6a;
      border-radius: 12px;
      text-align: center;
      padding: 24px 20px;
      margin-bottom: 24px;
    }
    .otp-label { font-size: 13px; color: #888; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
    .otp-code  { font-size: 42px; font-weight: 800; color: #2e7d32; letter-spacing: 10px; }
    .expire   { font-size: 13px; color: #e53935; text-align: center; margin-bottom: 24px; }
    .warning  { background: #fff3e0; border-left: 4px solid #fb8c00; border-radius: 6px; padding: 14px 18px; font-size: 13px; color: #e65100; margin-bottom: 24px; }
    .footer   { background: #f5f5f5; padding: 20px 40px; text-align: center; font-size: 12px; color: #aaa; }
    .footer a { color: #2e7d32; text-decoration: none; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>⚡ GreenElectric</h1>
      <p>Hệ thống thiết bị điện xanh</p>
    </div>

    <div class="body">
      <p class="greeting">Xin chào <strong>{{ $fullname }}</strong>,</p>
      <p class="info">
        Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.<br/>
        Vui lòng sử dụng mã OTP bên dưới để tiếp tục:
      </p>

      <div class="otp-box">
        <div class="otp-label">Mã xác minh OTP</div>
        <div class="otp-code">{{ $otp }}</div>
      </div>

      <p class="expire">⏰ Mã OTP có hiệu lực trong <strong>5 phút</strong>.</p>

      <div class="warning">
        ⚠️ <strong>Lưu ý bảo mật:</strong> Không chia sẻ mã này với bất kỳ ai.
        Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
      </div>
    </div>

    <div class="footer">
      © {{ date('Y') }} GreenElectric. Mọi thắc mắc liên hệ
      <a href="mailto:support@greenelectric.vn">support@greenelectric.vn</a>
    </div>
  </div>
</body>
</html>