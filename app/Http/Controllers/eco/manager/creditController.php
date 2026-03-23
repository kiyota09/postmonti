<?php

namespace App\Http\Controllers\eco\manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CreditAccount;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CreditController extends Controller
{
    /**
     * Display the Credit Architect ledger and pending order verifications.
     */
    public function credit(Request $request)
    {
        return Inertia::render('Dashboard/ECO/Manager/credit', [
            // Standard Credit Ledger accounts with client relationship
            'creditAccounts' => CreditAccount::with('client')
                ->when($request->search, function ($query, $search) {
                    $query->whereHas('client', function ($q) use ($search) {
                        $q->where('company_name', 'like', "%{$search}%");
                    })->orWhere('id', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10)
                ->withQueryString(),

            // Fetch Purchase Orders specifically in the 'credit_review' stage
            'pendingOrders' => PurchaseOrder::with('client')
                ->where('status', 'credit_review')
                ->latest()
                ->get(),

            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Handle the Credit Verification step of the B2B Workflow.
     * Updated: Creates/Updates a Credit Account record upon approval.
     */
    public function verifyOrder(Request $request, PurchaseOrder $po)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        if ($request->action === 'approve') {
            // 1. Move to Tier Management (HR Manager Process)
            $po->update([
                'status' => 'tier_assignment',
                'notes' => 'Credit check passed by ECO Manager.',
            ]);

            // 2. Reflect directly in the Credit Accounts table
            // Fixed: Removed 'customer_name' to match migration schema
            CreditAccount::updateOrCreate(
                ['client_id' => $po->client_id],
                [
                    'outstanding_balance' => DB::raw("outstanding_balance + {$po->total_amount}"),
                    'is_good_payer' => true,
                ]
            );

            return back()->with('success', "Order {$po->po_number} approved and moved to Tier Management.");
        }

        // Action: Reject - End the process for poor credit standing
        $po->update([
            'status' => 'rejected',
            'notes' => 'Order rejected by ECO Manager due to credit risk.',
        ]);

        return back()->with('error', "Order {$po->po_number} has been cancelled.");
    }

    /**
     * Store a new manual credit account entry in the ledger.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'outstanding_balance' => 'required|numeric|min:0',
        ]);

        CreditAccount::create($validated);

        return back()->with('success', 'Credit account created successfully.');
    }

    /**
     * Toggle the active status of a credit account.
     */
    public function toggleStatus(CreditAccount $account)
    {
        // Note: Migration uses 'is_good_payer' boolean rather than 'status' string
        $account->update([
            'is_good_payer' => ! $account->is_good_payer,
        ]);

        return back()->with('success', 'Client payment standing updated.');
    }

    /**
     * Remove a credit account from the ledger.
     */
    public function destroy(CreditAccount $account)
    {
        $account->delete();

        return back()->with('success', 'Account removed from ledger.');
    }
}
