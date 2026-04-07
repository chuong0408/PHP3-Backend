<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public User  $user,
        public array $items,
        public float $subtotal,
        public float $shippingFee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Đặt hàng thành công - Đơn hàng #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-success',
            with: [
                'order'       => $this->order,
                'user'        => $this->user,
                'items'       => $this->items,
                'subtotal'    => $this->subtotal,
                'shippingFee' => $this->shippingFee,
                'appName'     => config('app.name'),
                'appUrl'      => env('FRONTEND_URL', 'http://localhost:5173'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}