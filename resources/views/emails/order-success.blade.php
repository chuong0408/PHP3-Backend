<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            color: #333;
        }
        .wrapper {
            max-width: 620px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 60%, #43a047 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .header-icon {
            font-size: 60px;
            margin-bottom: 14px;
            display: block;
        }
        .header h1 {
            color: #ffffff;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .header p {
            color: rgba(255,255,255,0.85);
            font-size: 14px;
        }

        /* Body */
        .body { padding: 36px 30px; }
        .greeting {
            font-size: 17px;
            font-weight: 600;
            color: #1b5e20;
            margin-bottom: 10px;
        }
        .intro {
            color: #555;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        /* Order ID badge */
        .order-badge {
            background: #e8f5e9;
            border: 2px dashed #66bb6a;
            border-radius: 10px;
            padding: 16px 24px;
            text-align: center;
            margin-bottom: 28px;
        }
        .order-badge .label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .order-badge .order-id {
            font-size: 26px;
            font-weight: 800;
            color: #1b5e20;
            margin: 4px 0;
        }
        .order-badge .order-date {
            font-size: 12px;
            color: #888;
        }

        /* Section title */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #1b5e20;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #e8f5e9;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        /* Products table */
        .products-section { margin-bottom: 24px; }
        .product-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .product-row:last-child { border-bottom: none; }
        .product-name {
            flex: 1;
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        .product-sku {
            font-size: 11px;
            color: #aaa;
            margin-top: 2px;
        }
        .product-qty {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
        }
        .product-price {
            font-size: 14px;
            font-weight: 700;
            color: #e53935;
            white-space: nowrap;
        }

        /* Totals */
        .totals-section {
            background: #fafafa;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #555;
            padding: 5px 0;
        }
        .total-row.final {
            border-top: 2px solid #e8f5e9;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 16px;
            font-weight: 800;
            color: #1b5e20;
        }
        .total-row.final .price { color: #e53935; font-size: 18px; }
        .free { color: #2e7d32; font-weight: 600; }

        /* Info grid */
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }
        .info-box:last-child { padding: 0 0 0 8px; }
        .info-inner {
            background: #f7f9fc;
            border-radius: 10px;
            padding: 16px;
        }
        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        .info-value {
            font-size: 13px;
            color: #333;
            line-height: 1.6;
        }

        /* Payment badge */
        .payment-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #1b5e20;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Status timeline */
        .status-bar {
            background: #f7f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .status-steps {
            display: table;
            width: 100%;
        }
        .status-step {
            display: table-cell;
            text-align: center;
            position: relative;
        }
        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin: 0 auto 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            background: #e8f5e9;
            border: 2px solid #a5d6a7;
        }
        .step-circle.active {
            background: #2e7d32;
            border-color: #1b5e20;
        }
        .step-label {
            font-size: 11px;
            color: #888;
        }
        .step-label.active { color: #1b5e20; font-weight: 700; }

        /* CTA button */
        .btn-wrapper { text-align: center; margin: 28px 0; }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #1b5e20, #2e7d32);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 14px rgba(27,94,32,0.35);
        }

        /* Footer */
        .footer {
            background: #f7f9fc;
            padding: 24px 30px;
            text-align: center;
            border-top: 1px solid #e8f5e9;
        }
        .footer p { color: #aaa; font-size: 12px; line-height: 1.7; }
        .footer a { color: #2e7d32; text-decoration: none; }

        .divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }

        @media (max-width: 480px) {
            .info-box { display: block; width: 100%; padding: 0 0 12px 0; }
            .info-box:last-child { padding: 0; }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- Header -->
    <div class="header">
        <span class="header-icon">🎉</span>
        <h1>Đặt hàng thành công!</h1>
        <p>Cảm ơn bạn đã tin tưởng và mua sắm tại {{ $appName }}</p>
    </div>

    <!-- Body -->
    <div class="body">

        <p class="greeting">Xin chào {{ $user->fullname }},</p>
        <p class="intro">
            Chúng tôi đã nhận được đơn hàng của bạn và đang tiến hành xử lý.
            Bạn sẽ nhận được thông báo khi đơn hàng được xác nhận và chuẩn bị giao.
        </p>

        <!-- Order ID -->
        <div class="order-badge">
            <div class="label">Mã đơn hàng</div>
            <div class="order-id">#{{ $order->id }}</div>
            <div class="order-date">{{ \Carbon\Carbon::parse($order->created_at)->format('H:i, d/m/Y') }}</div>
        </div>

        <!-- Status -->
        <div class="status-bar">
            <div style="font-size:13px; color:#555; margin-bottom:14px;">Trạng thái đơn hàng</div>
            <div class="status-steps">
                <div class="status-step">
                    <div class="step-circle active">📋</div>
                    <div class="step-label active">Đặt hàng</div>
                </div>
                <div class="status-step">
                    <div class="step-circle">✅</div>
                    <div class="step-label">Xác nhận</div>
                </div>
                <div class="status-step">
                    <div class="step-circle">📦</div>
                    <div class="step-label">Đóng gói</div>
                </div>
                <div class="status-step">
                    <div class="step-circle">🚚</div>
                    <div class="step-label">Đang giao</div>
                </div>
                <div class="status-step">
                    <div class="step-circle">🏠</div>
                    <div class="step-label">Đã giao</div>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="products-section">
            <div class="section-title">📦 Sản phẩm đã đặt</div>
            @foreach($items as $item)
            <div class="product-row">
                <div style="flex:1;">
                    <div class="product-name">{{ $item['product_name'] ?? $item['product_sku_code'] }}</div>
                    <div class="product-sku">SKU: {{ $item['product_sku_code'] }}</div>
                </div>
                <div class="product-qty">x{{ $item['quantity'] }}</div>
                <div class="product-price">
                    {{ number_format(($item['price'] ?? 0) * $item['quantity'], 0, ',', '.') }}đ
                </div>
            </div>
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span>Tạm tính ({{ count($items) }} sản phẩm)</span>
                <span>{{ number_format($subtotal, 0, ',', '.') }}đ</span>
            </div>
            <div class="total-row">
                <span>Phí vận chuyển</span>
                <span class="{{ $shippingFee == 0 ? 'free' : '' }}">
                    {{ $shippingFee == 0 ? 'Miễn phí' : number_format($shippingFee, 0, ',', '.') . 'đ' }}
                </span>
            </div>
            <div class="total-row final">
                <span>Tổng cộng</span>
                <span class="price">{{ number_format($order->total, 0, ',', '.') }}đ</span>
            </div>
        </div>

        <!-- Info grid -->
        <div class="section-title">📋 Thông tin đơn hàng</div>
        <div class="info-grid">
            <div class="info-box">
                <div class="info-inner">
                    <div class="info-label">🚚 Giao hàng đến</div>
                    <div class="info-value">
                        <strong>{{ $user->fullname }}</strong><br>
                        {{ $order->phone }}<br>
                        {{ $order->address }}
                    </div>
                </div>
            </div>
            <div class="info-box">
                <div class="info-inner">
                    <div class="info-label">💳 Thanh toán</div>
                    <div class="info-value" style="margin-bottom:8px;">
                        <span class="payment-badge">
                            @php
                                $paymentLabels = [
                                    'cod'     => '💵 Thanh toán khi nhận hàng',
                                    'banking' => '🏦 Chuyển khoản ngân hàng',
                                    'momo'    => '💜 Ví MoMo',
                                    'vnpay'   => '💳 VNPay / Thẻ ATM',
                                ];
                            @endphp
                            {{ $paymentLabels[$order->payment] ?? $order->payment }}
                        </span>
                    </div>
                    <div class="info-value" style="color:#888; font-size:12px;">
                        @if($order->payment === 'cod')
                            Vui lòng chuẩn bị tiền mặt khi nhận hàng.
                        @elseif($order->payment === 'banking')
                            Vui lòng chuyển khoản để đơn hàng được xử lý.
                        @else
                            Thanh toán qua ví điện tử / thẻ.
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <hr class="divider">

        <!-- CTA -->
        <div class="btn-wrapper">
            <a href="{{ $appUrl }}/order/{{ $order->id }}" class="btn">Xem chi tiết đơn hàng →</a>
        </div>

        <p style="font-size:13px; color:#888; text-align:center; line-height:1.7;">
            Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ chúng tôi qua email
            <a href="mailto:{{ config('mail.from.address') }}" style="color:#2e7d32;">{{ config('mail.from.address') }}</a>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            Email này được gửi tự động từ <strong>{{ $appName }}</strong>.<br>
            Vui lòng không trả lời email này.
        </p>
        <p style="margin-top:8px;">
            <a href="{{ $appUrl }}">Trang chủ</a> ·
            <a href="{{ $appUrl }}/order-history">Lịch sử đơn hàng</a> ·
            <a href="{{ $appUrl }}/profile">Hồ sơ</a>
        </p>
    </div>

</div>
</body>
</html>