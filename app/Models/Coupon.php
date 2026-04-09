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
        'expires_at',
        'is_birthday_coupon',
    ];

    protected $casts = [
        'minordervalue' => 'float',
        'expires_at'         => 'datetime',
        'is_birthday_coupon' => 'boolean',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    // Quan hệ: một mã có thể được dùng bởi nhiều user
    public function usages()
    {
        return $this->hasMany(CouponUsage::class, 'coupon_code', 'coupon_code');
    }

    // Kiểm tra user đã dùng mã này chưa
    public function isUsedByUser(int $userId): bool
    {
        return $this->usages()->where('user_id', $userId)
        ->whereNotNull('used_at')
        ->exists();
    }
}
