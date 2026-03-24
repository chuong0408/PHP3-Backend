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
}