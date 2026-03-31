<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = 'order_details';

    public $timestamps = false;

    protected $fillable = [
        'product_sku_code',
        'orders_id',
        'quantity',
    ];

    // Quan hệ với ProductSku để lấy giá, tên sp...
    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_code', 'sku_code');
    }

    // Quan hệ với Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id');
    }
}