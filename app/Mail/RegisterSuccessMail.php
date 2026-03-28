<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegisterSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Chào mừng bạn đến với ' . config('app.name') . '!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.register-success',
            with: [
                'user'    => $this->user,
                'appName' => config('app.name'),
                'appUrl'  => env('FRONTEND_URL', 'http://localhost:5173'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}