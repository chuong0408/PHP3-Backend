<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'categories_id', 'brand_id', 'description', 'image_url'];

    // Quan hệ với Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'categories_id');
    }

    // Quan hệ với Brand
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
    // Quan hệ với ProductImg (bảng product_img)
    public function images()
    {
        return $this->hasMany(ProductImg::class, 'product_id');
    }
 
    // Quan hệ với ProductSku (bảng product_skus) — để lấy giá
    public function skus()
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }
}