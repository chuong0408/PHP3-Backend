<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table   = 'order_details';
    public $timestamps = false;

    protected $fillable = [
        'product_sku_code',
        'orders_id',
        'quantity',
    ];

    public function productSku()
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_code', 'sku_code');
    }
}