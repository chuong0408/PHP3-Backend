<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = ['name', 'parent_id'];

    // Quan hệ cha (self-referencing)
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Quan hệ con (self-referencing)
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Quan hệ với products
    public function products()
    {
        return $this->hasMany(Product::class, 'categories_id');
    }
}