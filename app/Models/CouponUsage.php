<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $table = 'coupon';
    public    $timestamps = false;

    protected $fillable = [
        'user_id',
        'coupon_code',
    ];

    // Quan hệ tới chi tiết mã
    public function couponDetail()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'coupon_code');
    }

    // Quan hệ tới user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}