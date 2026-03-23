<?php

namespace App\Http\Controllers\eco\manager;

use App\Http\Controllers\Controller;
use App\Models\PricingTier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookController extends Controller
{
    /**
     * Display the Pricing Architect dashboard.
     */
    public function book()
    {
        return Inertia::render('Dashboard/ECO/Manager/book', [
            'priceBooks' => [
                // Sorted by quantity to show the highest thresholds first in the UI
                'data' => PricingTier::orderBy('min_quantity', 'desc')->get(),
            ],
            // Eager load client/items and pre-calculate item sum for the "Run Analysis" UI
            'pendingTiering' => PurchaseOrder::with(['client', 'items'])
                ->where('status', 'tier_assignment')
                ->withSum('items as items_sum_quantity', 'quantity')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Store a new pricing tier threshold.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'min_quantity' => 'required|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        PricingTier::create([
            'name' => $validated['name'],
            'min_quantity' => $validated['min_quantity'],
            'discount_percentage' => $validated['discount_percentage'],
            'status' => 'active', // Matches your schema preference
        ]);

        return back()->with('success', "New tier '{$validated['name']}' successfully configured.");
    }

    /**
     * Workflow Step: Analyze order quantity and apply the matching tier.
     */
    public function applyTier(Request $request, PurchaseOrder $po)
    {
        // 1. Calculate total items in the bulk order
        $totalItems = $po->items()->sum('quantity');

        // 2. Find matching tier based on quantity rules.
        // We check for 'active' status to match the store method above.
        $tier = PricingTier::where('min_quantity', '<=', $totalItems)
            ->where('status', 'active')
            ->orderBy('min_quantity', 'desc')
            ->first();

        // 3. Define the values based on the tier found (Default to Normal if none)
        $tierName = $tier ? $tier->name : 'Normal';
        $discountPercent = $tier ? (float) $tier->discount_percentage : 0;

        // 4. Calculate actual money amounts
        $subtotal = (float) $po->subtotal;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $finalTotal = $subtotal - $discountAmount;

        // 5. Update the Order and move to Client Approval stage
        $po->update([
            'tier_level' => $tierName,
            'discount_amount' => $discountAmount,
            'total_amount' => $finalTotal,
            'status' => 'pending_client_approval', // Moves to client portal for acceptance
            'notes' => "Automated Tiering: Applied {$tierName} (-{$discountPercent}%) based on {$totalItems} items.",
        ]);

        return back()->with('success', "Analysis Complete: {$tierName} tier applied to {$po->po_number}.");
    }

    public function update(Request $request, \App\Models\PricingTier $tier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'min_quantity' => 'required|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $tier->update($validated);

        return back()->with('success', "Tier '{$tier->name}' successfully updated.");
    }
}
