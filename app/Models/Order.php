<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table   = 'orders';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'email', 'phone', 'address',
        'total', 'payment', 'status', 'created_at',
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'orders_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}