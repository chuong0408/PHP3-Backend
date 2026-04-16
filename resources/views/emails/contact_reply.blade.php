<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>Phản hồi liên hệ</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
    .wrap { max-width:600px; margin:30px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.1); }
    .hd { background:#1a73e8; color:#fff; padding:24px 32px; }
    .hd h1 { margin:0; font-size:20px; }
    .bd { padding:28px 32px; color:#333; line-height:1.7; }
    .box { border-radius:6px; padding:14px 18px; margin:14px 0; }
    .box.reply { background:#e8f4fd; border-left:4px solid #1a73e8; }
    .box.orig  { background:#f5f5f5; border-left:4px solid #bbb; }
    .box strong { display:block; margin-bottom:6px; font-size:13px; color:#555; }
    .ft { background:#f4f4f4; text-align:center; padding:14px; font-size:12px; color:#999; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="hd"><h1>{{ config('app.name') }}</h1></div>
    <div class="bd">
      <p>Xin chào <strong>{{ $fullname }}</strong>,</p>
      <p>Cảm ơn bạn đã liên hệ với chúng tôi. Dưới đây là phản hồi từ đội ngũ hỗ trợ:</p>

      <div class="box reply">
        <strong>✉️ Phản hồi của chúng tôi:</strong>
        {!! nl2br(e($replyMessage)) !!}
      </div>

      <div class="box orig">
        <strong>📩 Nội dung bạn đã gửi ({{ $contactSubject }}):</strong>
        {!! nl2br(e($userMessage)) !!}
      </div>

      <p>Nếu cần hỗ trợ thêm, vui lòng liên hệ lại với chúng tôi.</p>
      <p>Trân trọng,<br><strong>{{ config('app.name') }}</strong></p>
    </div>
    <div class="ft">© {{ date('Y') }} {{ config('app.name') }}. Bảo lưu mọi quyền.</div>
  </div>
</body>
</html>