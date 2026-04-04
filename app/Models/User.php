<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    // Bảng trong DB tên là "user" (không phải "users")
    protected $table = 'user';

    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'address',
        'brithday',
        'image',
        'role',
        'status',
        'otp',
        'otp_time',
        'password',
        'google_id',
        'avatar',
        'provider',
        'name',
    ];

    protected $casts = [
        'role'   => 'integer',
        'status' => 'integer',
    ];

    protected $hidden = [
        'password',
        'otp',
    ];

    public $timestamps = false;
}