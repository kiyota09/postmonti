<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\ClientQuotation;
use App\Models\ClientQuotationItem;
use App\Models\inv\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    /**
     * Store a newly created quotation from the client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rfq_reference' => 'nullable|string|max:50',
            'valid_until' => 'required|date|after_or_equal:today',
            'billing_address' => 'required|string',
            'shipping_address' => 'required|string',
            'lead_time' => 'nullable|string',
            'incoterms' => 'nullable|string',
            'shipping_method' => 'nullable|string',
            'payment_terms' => 'required|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'terms_conditions' => 'nullable|string',
            'custom_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        $client = auth('client')->user();

        // Calculate totals
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $discount = $item['discount'] ?? 0;
            $lineTotal = $item['quantity'] * $item['unit_price'] - $discount;
            $subtotal += $lineTotal;
        }
        $shippingCost = $validated['shipping_cost'] ?? 0;
        $tax = $validated['tax'] ?? 0;
        $grandTotal = $subtotal + $shippingCost + $tax;

        $quotation = ClientQuotation::create([
            'client_id' => $client->id,
            'quotation_number' => 'QT-'.date('Y').'-'.str_pad(ClientQuotation::whereYear('created_at', date('Y'))->count() + 1, 4, '0', STR_PAD_LEFT),
            'rfq_reference' => $validated['rfq_reference'],
            'issue_date' => now(),
            'valid_until' => $validated['valid_until'],
            'status' => 'sent',
            'billing_address' => $validated['billing_address'],
            'shipping_address' => $validated['shipping_address'],
            'lead_time' => $validated['lead_time'],
            'incoterms' => $validated['incoterms'],
            'shipping_method' => $validated['shipping_method'],
            'payment_terms' => $validated['payment_terms'],
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'currency' => $validated['currency'],
            'terms_conditions' => $validated['terms_conditions'],
            'custom_notes' => $validated['custom_notes'],
        ]);

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $discount = $item['discount'] ?? 0;
            $lineTotal = $item['quantity'] * $item['unit_price'] - $discount;
            ClientQuotationItem::create([
                'quotation_id' => $quotation->id,
                'product_id' => $item['product_id'],
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'product_description' => $product->description,
                'technical_specs' => $item['technical_specs'] ?? null,
                'quantity' => $item['quantity'],
                'unit_of_measure' => 'pcs',
                'unit_price' => $item['unit_price'],
                'discount' => $discount,
                'line_total' => $lineTotal,
            ]);
        }

        return redirect()->back()->with('success', 'Quotation sent to Monti Textile for review.');
    }

    /**
     * Accept a quotation after ECO's response.
     *
     * @param  \App\Models\ClientQuotation  $quotation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(ClientQuotation $quotation)
    {
        if ($quotation->client_id !== auth('client')->id() || $quotation->status !== 'under_review') {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $quotation->update(['status' => 'accepted', 'client_accepted_at' => now()]);

            // Create purchase order
            $po = PurchaseOrder::create([
                'client_id' => $quotation->client_id,
                'po_number' => 'PO-'.Str::upper(Str::random(8)),
                'subtotal' => $quotation->subtotal,
                'discount_amount' => 0,
                'total_amount' => $quotation->grand_total,
                'status' => 'credit_review',
                'tier_level' => null,
                'notes' => 'Converted from quotation: '.$quotation->quotation_number,
            ]);

            // Copy items
            foreach ($quotation->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Quotation accepted. Your order is now under review.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quotation acceptance failed: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to process acceptance.');
        }
    }

    /**
     * Reject a quotation after ECO's response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClientQuotation  $quotation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, ClientQuotation $quotation)
    {
        if ($quotation->client_id !== auth('client')->id() || $quotation->status !== 'under_review') {
            abort(403);
        }

        $request->validate(['reason' => 'nullable|string|max:500']);
        $quotation->update(['status' => 'rejected', 'client_rejected_at' => now()]);

        return redirect()->back()->with('success', 'Quotation rejected.');
    }
}