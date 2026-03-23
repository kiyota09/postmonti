<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the B2B Storefront with real product data and workflow tracking.
     */
    public function index()
    {
        $client = auth('client')->user();

        // Fetch products available for the B2B storefront
        $products = Product::where('status', 'published')
            ->latest()
            ->get();

        // Fetch current Purchase Orders with their workflow status for the tracking table
        $purchaseOrders = PurchaseOrder::where('client_id', $client->id)
            ->latest()
            ->get();

        return Inertia::render('CLIENT/dashboard', [
            'products' => $products,
            'purchaseOrders' => $purchaseOrders,
            'stats' => [
                'pending_orders' => $purchaseOrders->where('status', '!=', 'approved')->count(),
                'completed_orders' => $purchaseOrders->where('status', 'approved')->count(),
                'recent_orders' => $purchaseOrders->take(5),
            ],
        ]);
    }

    /**
     * Initialize the Purchase Order workflow.
     * Moves the order to 'credit_review' for the Eco Manager.
     */
    public function placeOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'total' => 'required|numeric',
        ]);

        $client = auth('client')->user();

        try {
            DB::beginTransaction();

            // 1. Create the Purchase Order in the Credit Review stage
            // This order will now appear in the Eco Manager's Credit Management module
            $po = PurchaseOrder::create([
                'client_id' => $client->id,
                'po_number' => 'PO-'.strtoupper(Str::random(8)),
                'subtotal' => $request->total,
                'total_amount' => $request->total,
                'status' => 'credit_review',
            ]);

            // 2. Map and save the order items
            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ]);
            }

            DB::commit();

            return back()->with('success', 'Your Purchase Order has been sent for Credit Verification.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to process order. Please try again.');
        }
    }

    /**
     * Handle the client's final acceptance after HR Manager applies tiers.
     */
    public function acceptQuotation(PurchaseOrder $po)
    {
        // Ensure the client owns this PO and it's in the correct stage
        if ($po->client_id !== auth('client')->id() || $po->status !== 'pending_client_approval') {
            abort(403);
        }

        $po->update(['status' => 'approved']);

        return back()->with('success', 'Quotation accepted. Your order is now being processed.');
    }
}
