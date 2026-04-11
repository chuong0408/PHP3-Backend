<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';

    // reviews chỉ có created_at, không có updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'product_sku_code',
        'comment',
        'rating',
        'status',
    ];

    protected $casts = [
        'rating'     => 'integer',
        'created_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_code', 'sku_code');
    }
}