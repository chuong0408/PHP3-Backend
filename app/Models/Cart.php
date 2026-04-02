<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'cart';

    public $timestamps = false;

    protected $fillable = ['product_sku_code', 'quantity', 'user_id'];

    /**
     * SKU (giá, tồn kho, trạng thái)
     */
    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_code', 'sku_code');
    }

    /**
     * Sản phẩm (tên, ảnh) — lấy qua SKU
     */
    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            ProductSku::class,
            'sku_code',        // FK trên product_skus
            'id',              // FK trên products
            'product_sku_code',// Local key trên cart
            'product_id'       // Local key trên product_skus
        );
    }
}