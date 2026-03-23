<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * Workflow Statuses:
     * 'credit_review' - Initial state for ECO Manager review
     * 'tier_assignment' - State for HR Manager business tiering
     * 'pending_client_approval' - Final quote sent to client
     * 'approved' - Finalized order
     */
    protected $fillable = [
        'client_id',
        'po_number',
        'subtotal',
        'discount_amount',
        'total_amount',
        'status',
        'tier_level',
        'notes',
    ];

    /**
     * Relationship: The items within this purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Relationship: The client who placed the order.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
