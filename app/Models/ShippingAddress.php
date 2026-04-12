<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $table = 'shipping_addresses';

    protected $fillable = [
        'user_id',
        'receiver_name',
        'phone',
        'province',
        'district',
        'ward',
        'detail_address',
        'is_default',
        'ghn_district_id',
        'ghn_ward_code',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Quan hệ: địa chỉ thuộc về 1 user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Helper: lấy địa chỉ đầy đủ thành 1 chuỗi
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->detail_address,
            $this->ward,
            $this->district,
            $this->province,
        ]));
    }
}
