<?php

namespace App\Models\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'name', 'category', 'subcategory', 'status',
        'color_tag', 'color_hex', 'color_name',
        'weight', 'dimensions', 'batch_size', 'lead_time',
        'unit_cost', 'selling_price', 'stock_on_hand',
        'moq', 'certification', 'description',
    ];

    protected $casts = [
        'unit_cost' => 'float',
        'selling_price' => 'float',
        'stock_on_hand' => 'integer',
        'batch_size' => 'integer',
        'moq' => 'integer',
    ];

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('sort_order');
    }

    public function bom(): HasMany
    {
        return $this->hasMany(ProductBom::class)->orderBy('sort_order');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order');
    }

    public function images(): HasMany   // ← ADD THIS
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function getGrossMarginAttribute(): float
    {
        if ($this->selling_price <= 0) {
            return 0;
        }

        return round((($this->selling_price - $this->unit_cost) / $this->selling_price) * 100, 1);
    }

    public static function nextProductId(): string
    {
        $last = static::orderByDesc('id')->value('product_id');
        if (! $last) {
            return 'PRD-001';
        }
        $num = (int) substr($last, 4) + 1;

        return 'PRD-'.str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
