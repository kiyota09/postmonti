<?php

namespace App\Http\Controllers\SCM\Manager;

use App\Http\Controllers\Controller;
use App\Models\SCM\ProcurementPayment;
use App\Models\SCM\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index()
    {
        $invoices = PurchaseInvoice::where('status', 'unpaid')
            ->orderBy('due_date')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'po_number' => $inv->po_number,
                'supplier_name' => $inv->supplier_name,
                'invoice_date' => $inv->invoice_date,
                'due_date' => $inv->due_date,
                'amount' => $inv->amount,
                'payment_terms' => $inv->payment_terms,
            ]);

        $payments = ProcurementPayment::orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(fn ($pay) => [
                'id' => $pay->id,
                'payment_number' => $pay->payment_number,
                'invoice_number' => $pay->invoice_number,
                'supplier_name' => $pay->supplier_name,
                'paid_date' => $pay->paid_date,
                'amount' => $pay->amount,
                'method' => $pay->method,
                'bank_reference' => $pay->bank_reference,
                'remarks' => $pay->remarks,
                'status' => $pay->status,
            ]);

        return Inertia::render('Dashboard/SCM/Manager/Payment', [
            'pendingInvoices' => $invoices,
            'paymentHistory' => $payments,
            'stats' => [
                'totalPending' => $invoices->sum('amount'),
                'countPending' => $invoices->count(),
            ],
        ]);
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:purchase_invoices,id',
            'invoice_number' => 'required|string',
            'supplier_name' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'bank_reference' => 'required|string',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $invoice = PurchaseInvoice::findOrFail($validated['invoice_id']);

            if ($invoice->status === 'paid') {
                throw new \Exception('Invoice already paid.');
            }

            ProcurementPayment::create([
                'payment_number' => $this->generatePaymentNumber(),
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'supplier_name' => $invoice->supplier_name,
                'paid_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'bank_reference' => $validated['bank_reference'],
                'remarks' => $validated['remarks'],
                'status' => 'cleared',
            ]);

            $invoice->update(['status' => 'paid']);

            DB::commit();

            return redirect()->back()->with('success', 'Payment processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function generatePaymentNumber(): string
    {
        $year = now()->format('Y');
        $count = ProcurementPayment::whereYear('created_at', $year)->count() + 1;

        return 'PAY-'.$year.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
