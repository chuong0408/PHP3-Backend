<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table      = 'coupon_details';
    protected $primaryKey = 'coupon_code';
    public    $incrementing = false;
    protected $keyType    = 'string';

    // coupon_details chỉ có created_at, không có updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'coupon_code',
        'discount',
        'description',
        'minordervalue',
    ];

    protected $casts = [
        'minordervalue' => 'float',
    ];

    // Quan hệ: một mã có thể được dùng bởi nhiều user
    public function usages()
    {
        return $this->hasMany(CouponUsage::class, 'coupon_code', 'coupon_code');
    }

    // Kiểm tra user đã dùng mã này chưa
    public function isUsedByUser(int $userId): bool
    {
        return $this->usages()->where('user_id', $userId)->exists();
    }
}