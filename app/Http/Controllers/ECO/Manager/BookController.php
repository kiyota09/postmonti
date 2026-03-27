<?php

namespace App\Http\Controllers\ECO\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PricingTier;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookController extends Controller
{
    /**
     * Display the pricing architect dashboard.
     *
     * This view shows all active pricing tiers (volume, holiday, client-specific)
     * and a list of orders awaiting tier assignment. It also provides a list
     * of all clients for creating client-specific tiers.
     *
     * @return \Inertia\Response
     */
    public function book()
    {
        // Retrieve all pricing tiers, ordered by type and then by min_quantity
        $priceBooks = PricingTier::orderByRaw("FIELD(type, 'client_specific', 'holiday', 'volume')")
            ->orderBy('min_quantity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tier) {
                // For client-specific tiers, load the client data
                if ($tier->type === 'client_specific' && $tier->client_id) {
                    $tier->client = Client::find($tier->client_id);
                }

                return $tier;
            });

        // Orders that are ready for tier assignment (after credit approval)
        $pendingTiering = PurchaseOrder::with(['client', 'items'])
            ->where('status', 'tier_assignment')
            ->withSum('items as items_sum_quantity', 'quantity')
            ->latest()
            ->get();

        // All clients for dropdown selection
        $clients = Client::select('id', 'company_name')->orderBy('company_name')->get();

        return Inertia::render('Dashboard/ECO/Manager/Book', [
            'priceBooks' => $priceBooks,
            'pendingTiering' => $pendingTiering,
            'clients' => $clients,
        ]);
    }

    /**
     * Store a new pricing tier.
     *
     * Validates input based on tier type, then creates the tier with the appropriate
     * fields (min_quantity for volume, client_id for client-specific, holiday_date for holiday).
     * Optional start_date and end_date can be provided for all types.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|in:volume,holiday,client_specific',
            'min_quantity' => 'nullable|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'client_id' => 'required_if:type,client_specific|exists:clients,id',
            'holiday_date' => 'required_if:type,holiday|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'discount_percentage' => $validated['discount_percentage'],
            'status' => 'active',
        ];

        if ($validated['type'] === 'volume') {
            $data['min_quantity'] = $validated['min_quantity'];
        } elseif ($validated['type'] === 'client_specific') {
            $data['client_id'] = $validated['client_id'];
        } elseif ($validated['type'] === 'holiday') {
            $data['holiday_date'] = $validated['holiday_date'];
        }

        if (isset($validated['start_date'])) {
            $data['start_date'] = $validated['start_date'];
        }
        if (isset($validated['end_date'])) {
            $data['end_date'] = $validated['end_date'];
        }

        PricingTier::create($data);

        return back()->with('success', "New tier '{$validated['name']}' created.");
    }

    /**
     * Update an existing pricing tier.
     *
     * Resets irrelevant fields (min_quantity, client_id, holiday_date) based on the new type.
     * Preserves the ability to set or clear start_date and end_date.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, PricingTier $tier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|in:volume,holiday,client_specific',
            'min_quantity' => 'nullable|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'client_id' => 'required_if:type,client_specific|exists:clients,id',
            'holiday_date' => 'required_if:type,holiday|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'discount_percentage' => $validated['discount_percentage'],
        ];

        if ($validated['type'] === 'volume') {
            $data['min_quantity'] = $validated['min_quantity'];
            $data['client_id'] = null;
            $data['holiday_date'] = null;
        } elseif ($validated['type'] === 'client_specific') {
            $data['client_id'] = $validated['client_id'];
            $data['min_quantity'] = null;
            $data['holiday_date'] = null;
        } elseif ($validated['type'] === 'holiday') {
            $data['holiday_date'] = $validated['holiday_date'];
            $data['min_quantity'] = null;
            $data['client_id'] = null;
        }

        if (isset($validated['start_date'])) {
            $data['start_date'] = $validated['start_date'];
        } else {
            $data['start_date'] = null;
        }
        if (isset($validated['end_date'])) {
            $data['end_date'] = $validated['end_date'];
        } else {
            $data['end_date'] = null;
        }

        $tier->update($data);

        return back()->with('success', "Tier '{$tier->name}' updated.");
    }

    /**
     * Apply the appropriate pricing tier to a purchase order.
     *
     * The priority is:
     * 1. Client-specific tier (if active and within date range)
     * 2. Holiday tier (if today matches holiday_date and within date range)
     * 3. Volume tier (highest min_quantity <= total items, within date range)
     *
     * Updates the order with the applied tier name, discount amount, total amount,
     * and moves the order status to 'pending_client_approval'.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function applyTier(Request $request, PurchaseOrder $po)
    {
        $totalItems = $po->items()->sum('quantity');
        $today = Carbon::today()->toDateString();

        // 1. Client-specific tier (active, with optional date range)
        $tier = PricingTier::where('type', 'client_specific')
            ->where('client_id', $po->client_id)
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->first();

        // 2. Holiday tier (exact date match, plus date range)
        if (! $tier) {
            $tier = PricingTier::where('type', 'holiday')
                ->where('holiday_date', $today)
                ->where('status', 'active')
                ->where(function ($q) use ($today) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->first();
        }

        // 3. Volume tier (active, date range, and min quantity)
        if (! $tier) {
            $tier = PricingTier::where('type', 'volume')
                ->where('min_quantity', '<=', $totalItems)
                ->where('status', 'active')
                ->where(function ($q) use ($today) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->orderBy('min_quantity', 'desc')
                ->first();
        }

        $tierName = $tier ? $tier->name : 'Normal';
        $discountPercent = $tier ? (float) $tier->discount_percentage : 0;

        $subtotal = (float) $po->subtotal;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $finalTotal = $subtotal - $discountAmount;

        $po->update([
            'tier_level' => $tierName,
            'discount_amount' => $discountAmount,
            'total_amount' => $finalTotal,
            'status' => 'pending_client_approval',
            'notes' => "Applied {$tierName} tier (-{$discountPercent}%) based on {$totalItems} items.",
        ]);

        return back()->with('success', "Tier applied to {$po->po_number}.");
    }
}
