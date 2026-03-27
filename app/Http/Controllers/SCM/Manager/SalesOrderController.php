<?php

namespace App\Http\Controllers\SCM\Manager;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Inertia\Inertia;

class SalesOrderController extends Controller
{
    /**
     * Display sales orders that have been approved by ECO and are ready for SCM.
     */
    public function index()
    {
        // Eager load the queue relationship to avoid N+1 queries
        $orders = PurchaseOrder::with(['client', 'items.product', 'queue'])
            ->whereHas('queue', function ($q) {
                $q->where('stage', 'scm_received');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Dashboard/SCM/Manager/SalesOrder', [
            'orders' => $orders,
        ]);
    }

    /**
     * Forward a sales order to the Inventory module for material availability check.
     */
    public function forwardToINV(PurchaseOrder $order)
    {
        // Load the queue relationship to avoid extra query
        $order->load('queue');
        $queue = $order->queue;

        if (! $queue || $queue->stage !== 'scm_received') {
            return redirect()->back()->withErrors(['error' => 'Order not in correct stage for forwarding to Inventory.']);
        }

        $queue->update([
            'stage' => 'inv_check',
            'inv_checked_at' => now(),
            'notes' => ($queue->notes ? $queue->notes."\n" : '').'Forwarded to INV on '.now(),
        ]);

        return redirect()->back()->with('success', 'Order forwarded to Inventory for material check.');
    }
}
