<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSku extends Model
{
    protected $table = 'product_skus';

    // Primary key là sku_code (varchar), không tự tăng
    protected $primaryKey = 'sku_code';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['sku_code', 'product_id', 'price', 'quantity', 'status'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}