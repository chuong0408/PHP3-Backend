<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'fullname', 'email', 'phone', 'subject', 'message',
        'status', 'reply_message', 'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];
}