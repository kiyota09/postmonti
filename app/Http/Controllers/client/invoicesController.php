<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\CreditAccount;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    /**
     * Display the client's financial statements and invoice history.
     */
    public function invoices()
    {
        // 1. Get the authenticated client using the 'client' guard
        $client = Auth::guard('client')->user();

        // 2. Fetch approved purchase orders as live invoices
        $invoices = PurchaseOrder::where('client_id', $client->id)
            ->where('status', 'approved')
            ->latest()
            ->get();

        // 3. Fetch credit standing from the ledger
        $creditAccount = CreditAccount::where('client_id', $client->id)->first();

        // 4. Calculate real-time financial metrics
        $totalOutstanding = $creditAccount ? (float) $creditAccount->outstanding_balance : 0;
        $creditLimit = (float) $client->credit_limit;

        // Calculate percentage of credit used
        $utilization = $creditLimit > 0 ? ($totalOutstanding / $creditLimit) * 100 : 0;

        return Inertia::render('CLIENT/invoices', [
            // Passing live data to the Vue frontend
            'invoices' => $invoices,
            'stats' => [
                'totalOutstanding' => number_format($totalOutstanding, 2),
                'utilization' => round($utilization, 1),
                'pendingCount' => $invoices->where('status', 'pending')->count(),
                'lastPaymentDate' => $creditAccount ? $creditAccount->updated_at->format('M d, Y') : 'No records',
            ],
        ]);
    }
}
