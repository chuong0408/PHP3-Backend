<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BirthdayCouponMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $couponCode,
        public string $discount,
        public string $expiresAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎂 Chúc Mừng Sinh Nhật ' . $this->user->fullname . '! Quà Tặng Đặc Biệt Từ Chúng Tôi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.birthday-coupon',
        );
    }
}