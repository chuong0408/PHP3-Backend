<?php
// ── app/Models/ProductVariant.php ─────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table    = 'product_variants';
    public $timestamps  = false; // chỉ có created_at, không có updated_at

    protected $fillable = ['variant_name', 'product_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function options()
    {
        return $this->hasMany(VariantOption::class, 'product_variant_id')->orderBy('id');
    }
}


// ────────────────────────────────────────────────────────────────────────────
// ── app/Models/VariantOption.php ──────────────────────────────────────────────

// namespace App\Models;
// use Illuminate\Database\Eloquent\Model;

class VariantOption extends Model
{
    protected $table   = 'variant_options';
    public $timestamps = false;

    protected $fillable = ['product_variant_id', 'option_values'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function combinations()
    {
        return $this->hasMany(ProductCombinationOption::class, 'options_id');
    }
}


// ────────────────────────────────────────────────────────────────────────────
// ── app/Models/ProductCombinationOption.php ───────────────────────────────────

// namespace App\Models;
// use Illuminate\Database\Eloquent\Model;

class ProductCombinationOption extends Model
{
    protected $table   = 'product_combination_options';
    public $timestamps = false;

    protected $fillable = ['options_id', 'sku_code'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function option()
    {
        return $this->belongsTo(VariantOption::class, 'options_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_code', 'sku_code');
    }
}