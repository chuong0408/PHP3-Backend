<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
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
    ];

    protected $hidden = [
        'password',
        'otp',
    ];

    // Tắt timestamps tự động vì bảng user không có created_at / updated_at chuẩn Laravel
    public $timestamps = false;
}