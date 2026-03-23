<?php

namespace App\Models\Scm;

use Illuminate\Database\Eloquent\Model;

class ScmPurchaseOrderItem extends Model
{
    protected $table = 'scm_purchase_order_items';

    protected $fillable = [
        'scm_purchase_order_id', 'material_id', 'material_name',
        'qty', 'unit', 'unit_price', 'total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(ScmPurchaseOrder::class, 'scm_purchase_order_id');
    }
}
