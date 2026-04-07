<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ProductCombinationOption extends Model
{
    protected $table = 'product_combination_options';

    public $timestamps = false;

    protected $fillable = ['options_id', 'sku_code', 'created_at'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_at = Carbon::now();
        });
    }

    public function option()
    {
        return $this->belongsTo(VariantOption::class, 'options_id');
    }
}