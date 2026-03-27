<?php

namespace App\Models\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = ['mat_id', 'name', 'category', 'unit', 'reorder_point', 'unit_cost'];

    protected $casts = [
        'unit_cost' => 'float',
        'reorder_point' => 'integer',
    ];

    public function warehouseMaterials(): HasMany
    {
        return $this->hasMany(WarehouseMaterial::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_materials')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function productBoms(): HasMany
    {
        return $this->hasMany(ProductBom::class);
    }

    // Auto-generate next mat_id
    public static function nextMatId(): string
    {
        $last = static::orderByDesc('id')->value('mat_id');
        if (! $last) {
            return 'MAT-001';
        }
        $num = (int) substr($last, 4) + 1;

        return 'MAT-'.str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
