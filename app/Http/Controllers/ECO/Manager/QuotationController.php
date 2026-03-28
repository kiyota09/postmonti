<?php

namespace App\Http\Controllers\ECO\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientQuotation;
use App\Models\ClientQuotationItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuotationController extends Controller
{
    /**
     * Display a list of client quotation requests.
     */
    public function index()
    {
        $quotations = ClientQuotation::with(['client', 'items.product'])
            ->whereIn('status', ['sent', 'under_review'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Dashboard/ECO/Manager/Quotations', [
            'quotations' => $quotations,
        ]);
    }

    /**
     * Show a specific quotation in detail.
     */
    public function show($id)
    {
        $quotation = ClientQuotation::with(['client', 'items.product'])->findOrFail($id);

        return Inertia::render('Dashboard/ECO/Manager/Quotations/Show', [
            'quotation' => $quotation,
        ]);
    }

    /**
     * Process ECO's response to a client quotation.
     * Can either approve with modifications or reject.
     */
    public function respond(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
            // If approving, we allow updating certain fields
            'grand_total' => 'nullable|numeric',
            'valid_until' => 'nullable|date',
            'lead_time' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:client_quotation_items,id',
            'items.*.quantity' => 'nullable|numeric',
            'items.*.unit_price' => 'nullable|numeric',
            'items.*.discount' => 'nullable|numeric',
        ]);

        $quotation = ClientQuotation::findOrFail($id);

        if ($validated['action'] === 'reject') {
            $quotation->update([
                'status' => 'rejected',
                'client_rejected_at' => Carbon::now(),
                'custom_notes' => ($quotation->custom_notes ? $quotation->custom_notes."\n\n" : '').'Rejection reason: '.($validated['notes'] ?? 'No reason provided'),
            ]);
            $message = 'Quotation rejected.';
        } else {
            // Update quotation main fields
            $updateData = [
                'status' => 'under_review',
                'valid_until' => $validated['valid_until'] ?? $quotation->valid_until,
                'lead_time' => $validated['lead_time'] ?? $quotation->lead_time,
                'payment_terms' => $validated['payment_terms'] ?? $quotation->payment_terms,
                'custom_notes' => ($quotation->custom_notes ? $quotation->custom_notes."\n\n" : '').'ECO Response: '.($validated['notes'] ?? 'No additional notes'),
            ];

            if (isset($validated['grand_total'])) {
                // Recalculate totals based on line items if provided
                $subtotal = 0;
                if (isset($validated['items'])) {
                    foreach ($validated['items'] as $itemData) {
                        if (isset($itemData['quantity']) && isset($itemData['unit_price'])) {
                            $discount = $itemData['discount'] ?? 0;
                            $subtotal += ($itemData['quantity'] * $itemData['unit_price']) - $discount;
                        }
                    }
                    $updateData['subtotal'] = $subtotal;
                    $updateData['grand_total'] = $subtotal + $quotation->shipping_cost + $quotation->tax;
                } else {
                    $updateData['grand_total'] = $validated['grand_total'];
                }
            }

            $quotation->update($updateData);

            // Update line items if provided
            if (isset($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = ClientQuotationItem::find($itemData['id']);
                        if ($item) {
                            $lineTotal = ($itemData['quantity'] * $itemData['unit_price']) - ($itemData['discount'] ?? 0);
                            $item->update([
                                'quantity' => $itemData['quantity'],
                                'unit_price' => $itemData['unit_price'],
                                'discount' => $itemData['discount'] ?? 0,
                                'line_total' => $lineTotal,
                            ]);
                        }
                    }
                }
            }

            $message = 'Quotation updated and now under client review.';
        }

        return redirect()->back()->with('success', $message);
    }
}
