<?php

namespace App\Http\Controllers\ECO\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientQuotation;
use App\Models\CreditAccount;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CreditController extends Controller
{
    /**
     * Display the Credit Architect ledger and pending order verifications.
     *
     * @return \Inertia\Response
     */
    public function credit(Request $request)
    {
        // Build query for credit accounts with optional search
        $creditAccounts = CreditAccount::with('client')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('client', function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Orders that need credit approval before tiering
        $pendingOrders = PurchaseOrder::with('client')
            ->where('status', 'credit_review')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/ECO/Manager/credit', [
            'creditAccounts' => $creditAccounts,
            'pendingOrders' => $pendingOrders,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Handle the Credit Verification step of the B2B Workflow.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyOrder(Request $request, PurchaseOrder $po)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        if ($request->action === 'approve') {
            // Move order to tier assignment stage
            $po->update([
                'status' => 'tier_assignment',
                'notes' => 'Credit check passed by ECO Manager.',
            ]);

            // Update or create credit account for this client
            CreditAccount::updateOrCreate(
                ['client_id' => $po->client_id],
                [
                    'outstanding_balance' => DB::raw("outstanding_balance + {$po->total_amount}"),
                    'is_good_payer' => true,
                ]
            );

            return back()->with('success', "Order {$po->po_number} approved and moved to Tier Management.");
        }

        // Reject order
        $po->update([
            'status' => 'rejected',
            'notes' => 'Order rejected by ECO Manager due to credit risk.',
        ]);

        return back()->with('error', "Order {$po->po_number} has been cancelled.");
    }

    /**
     * Retrieve full financial history for a client (orders, quotations, credit account).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clientHistory(Client $client)
    {
        $orders = PurchaseOrder::where('client_id', $client->id)
            ->latest()
            ->get();

        $quotations = ClientQuotation::where('client_id', $client->id)
            ->latest()
            ->get();

        $creditAccount = CreditAccount::where('client_id', $client->id)->first();

        return response()->json([
            'orders' => $orders,
            'quotations' => $quotations,
            'credit_account' => $creditAccount,
        ]);
    }

    /**
     * Store a new manual credit account entry in the ledger.
     *
     * @return \Illuminate\Http\RedirectResponse
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
     * Toggle the active status of a credit account (good payer flag).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(CreditAccount $account)
    {
        $account->update([
            'is_good_payer' => ! $account->is_good_payer,
        ]);

        return back()->with('success', 'Client payment standing updated.');
    }

    /**
     * Remove a credit account from the ledger.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CreditAccount $account)
    {
        $account->delete();

        return back()->with('success', 'Account removed from ledger.');
    }
}
