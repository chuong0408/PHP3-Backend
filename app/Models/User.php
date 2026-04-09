<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'user';

    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'address',
        'birthday',
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

        // ── Relationships ──────────────────────────────────────────
    public function shippingAddresses()
    {
        return $this->hasMany(ShippingAddress::class, 'user_id');
    }
 
    public function defaultAddress()
    {
        return $this->hasOne(ShippingAddress::class, 'user_id')->where('is_default', true);
    }
}