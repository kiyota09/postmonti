<?php

namespace App\Models\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'path',
        'original_name',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Full public URL for the image.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
