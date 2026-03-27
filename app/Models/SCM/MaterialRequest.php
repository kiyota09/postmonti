<?php

namespace App\Models\SCM;

use App\Models\INV\Material;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'req_number', 'material_id', 'material_name', 'category', 'unit',
        'current_stock', 'reorder_point', 'required_qty', 'urgency',
        'requested_by', 'requested_at', 'status', 'notes',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'current_stock' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'required_qty' => 'decimal:2',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function rfqs()
    {
        return $this->hasMany(RequestForQuotation::class, 'mr_id');
    }
}
