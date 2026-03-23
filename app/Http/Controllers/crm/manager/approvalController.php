<?php

namespace App\Http\Controllers\crm\manager;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    /**
     * Display the CRM Approval Queue.
     */
    public function approval()
    {
        $items = PurchaseOrder::with('client')
            ->whereIn('status', [
                'credit_review',
                'tier_assignment',
                'pending_client_approval',
            ])
            ->latest()
            ->get();

        // ✅ Render the dedicated approval component instead of the main dashboard
        return Inertia::render('Dashboard/CRM/Manager/approval', [
            'approvalItems' => $items,
        ]);
    }

    /**
     * Process the approval or rejection of an order.
     */
    public function process(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
        ]);

        $order = PurchaseOrder::findOrFail($id);

        $order->update([
            'status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
            'notes' => $validated['notes'] ?? $order->notes,
        ]);

        return back()->with('success', 'Order '.$validated['action'].'ed successfully.');
    }
}
