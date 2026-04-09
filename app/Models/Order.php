<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    // orders bảng có created_at nhưng KHÔNG có updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'phone',
        'address',
        'total',
        'payment',
        'status',
        'created_at',
        'coupon_code',
        'discount',
    ];

    protected $casts = [
        'total'      => 'float',
        'created_at' => 'datetime',
    ];

    // Quan hệ với OrderDetail
    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'orders_id');
    }

    // Quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
