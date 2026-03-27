<?php

namespace App\Http\Controllers\ECO\Manager;

use App\Http\Controllers\Controller;
use App\Models\OrderQueue;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderManagementController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with(['client', 'items.product'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        // Attach queue stage info
        $orders->getCollection()->transform(function ($order) {
            $queue = OrderQueue::where('purchase_order_id', $order->id)->first();
            $order->queue_stage = $queue ? $queue->stage : null;

            return $order;
        });

        return Inertia::render('Dashboard/ECO/Manager/orders', [
            'orders' => $orders,
        ]);
    }

    public function approveOrder(PurchaseOrder $order)
    {
        // Only if order is in credit_review and credit check passed
        if ($order->status !== 'credit_review') {
            return back()->withErrors(['error' => 'Order not in credit review.']);
        }

        // Update order status to tier_assignment (or directly to approved if no tiering)
        $order->update(['status' => 'tier_assignment']);

        // Create queue entry
        OrderQueue::updateOrCreate(
            ['purchase_order_id' => $order->id],
            ['stage' => 'eco_approved', 'notes' => 'Approved by ECO']
        );

        return back()->with('success', 'Order approved and queued for processing.');
    }

    public function rejectOrder(Request $request, PurchaseOrder $order)
    {
        $request->validate(['reason' => 'required|string']);
        $order->update(['status' => 'rejected', 'notes' => $request->reason]);

        // Optionally delete queue entry
        OrderQueue::where('purchase_order_id', $order->id)->delete();

        return back()->with('success', 'Order rejected.');
    }

    // This is the endpoint that sends the order to SCM (triggered by some action or automatic)
    public function sendToSCM(PurchaseOrder $order)
    {
        $queue = OrderQueue::where('purchase_order_id', $order->id)->first();
        if (! $queue || $queue->stage !== 'eco_approved') {
            return back()->withErrors(['error' => 'Order not ready for SCM.']);
        }

        // In a real system, you might trigger an event or call SCM API.
        // For now, just update queue stage.
        $queue->update(['stage' => 'scm_received', 'scm_received_at' => now()]);

        return back()->with('success', 'Order sent to Supply Chain Management.');
    }
}
