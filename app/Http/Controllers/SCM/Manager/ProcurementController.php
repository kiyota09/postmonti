<?php

namespace App\Http\Controllers\SCM\Manager;

use App\Http\Controllers\Controller;
use App\Models\inv\Warehouse;
use App\Models\SCM\MaterialRequest;
use App\Models\SCM\ProcurementPayment;
use App\Models\SCM\PurchaseInvoice;
use App\Models\SCM\RequestForQuotation;
use App\Models\SCM\RfqResponse;
use App\Models\SCM\ScmPurchaseOrder;
use App\Models\SCM\ScmPurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProcurementController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // MAIN PAGE RENDER
    // ──────────────────────────────────────────────────────────────

    public function procurement()
    {
        try {
            // ── FETCH WAREHOUSES FOR DYNAMIC RFQ DROPDOWN ────────────
            $warehouses = Warehouse::orderBy('name')->get()->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'location' => $w->location,
            ]);

            $materialRequests = MaterialRequest::with('material')
                ->orderByRaw("FIELD(urgency, 'High', 'Medium', 'Low')")
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'req_number' => $r->req_number ?? 'N/A',
                    'material_id' => $r->material_id,
                    'material_name' => $r->material?->name ?? $r->material_name ?? 'N/A',
                    'category' => $r->material?->category ?? $r->category ?? 'N/A',
                    'unit' => $r->material?->unit ?? $r->unit ?? 'pcs',
                    'current_stock' => $r->current_stock ?? 0,
                    'reorder_point' => $r->reorder_point ?? 0,
                    'required_qty' => $r->required_qty ?? 0,
                    'urgency' => $r->urgency ?? 'Medium',
                    'requested_by' => $r->requested_by ?? 'System',
                    'requested_at' => $r->requested_at ? \Carbon\Carbon::parse($r->requested_at)->format('Y-m-d') : null,
                    'status' => $r->status ?? 'pending',
                    'notes' => $r->notes ?? '',
                ]);

            // ── DYNAMIC SUPPLIERS QUERY ──────────────────────────────
            // ONLY FETCH OFFICIAL/APPROVED VENDORS FOR PROCUREMENT
            $suppliers = Supplier::whereHas('vendorRegistration', function ($query) {
                $query->where('status', 'approved');
            })
                ->with('vendorRegistration.requirements')
                ->orderBy('business_name')
                ->get()
                ->map(fn ($s) => [
                'id' => $s->id,
                'business_name' => $s->business_name,
                'representative_name' => $s->representative_name ?? '',
                'email' => $s->email,
                'phone_number' => $s->phone_number,
                'address' => $s->address ?? '',
                'rating' => $s->rating ?? 5.0,
                'status' => 'Official Vendor',
                // Fallback to all categories to ensure they show up in the Vue UI filter
                'categories' => $s->categories ? json_decode($s->categories, true) : ['Fabric', 'Trim', 'Chemicals', 'Packaging'],
                'requirements' => collect($s->vendorRegistration?->requirements ?? [])->map(fn ($req) => [
                    'name' => $req->requirement_name,
                    'value' => $req->value,
                ])->toArray(),
            ]);

            $rfqs = RequestForQuotation::with('responses.supplier')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($rfq) => [
                    'id' => $rfq->id,
                    'rfq_number' => $rfq->rfq_number,
                    'mr_ref' => $rfq->mr_ref,
                    'material_name' => $rfq->material_name ?? 'N/A',
                    'category' => $rfq->category ?? 'N/A',
                    'unit' => $rfq->unit ?? 'pcs',
                    'required_qty' => $rfq->required_qty ?? 0,
                    'deadline' => $rfq->deadline ? \Carbon\Carbon::parse($rfq->deadline)->format('Y-m-d') : null,
                    'delivery_address' => $rfq->delivery_address ?? 'Main Warehouse',
                    'sent_at' => $rfq->sent_at ? \Carbon\Carbon::parse($rfq->sent_at)->format('Y-m-d') : null,
                    'status' => $rfq->status ?? 'sent',
                    'notes' => $rfq->notes ?? '',
                    'responses' => collect($rfq->responses ?? [])->map(fn ($r) => [
                        'id' => $r->id,
                        'supplier_id' => $r->supplier_id,
                        'supplier_name' => $r->supplier?->business_name ?? $r->supplier_name ?? 'Unknown Supplier',
                        'unit_price' => $r->unit_price ?? 0,
                        'total_price' => $r->total_price ?? 0,
                        'lead_time' => $r->lead_time ?? 'N/A',
                        'validity_date' => $r->validity_date ? \Carbon\Carbon::parse($r->validity_date)->format('Y-m-d') : null,
                        'payment_terms' => $r->payment_terms ?? 'Net 30',
                        'notes' => $r->notes ?? '',
                        'status' => $r->status ?? 'pending_review',
                        'submitted_at' => $r->submitted_at ? \Carbon\Carbon::parse($r->submitted_at)->format('Y-m-d') : null,
                    ]),
                ]);

            $purchaseOrders = ScmPurchaseOrder::with('items')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($po) => [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier_id' => $po->supplier_id,
                    'supplier_name' => $po->supplier_name ?? 'N/A',
                    'rfq_ref' => $po->rfq_ref,
                    'issued_date' => $po->issued_date ? \Carbon\Carbon::parse($po->issued_date)->format('Y-m-d') : null,
                    'expected_delivery' => $po->expected_delivery ?? 'N/A',
                    'status' => $po->status ?? 'draft',
                    'subtotal' => $po->subtotal ?? 0,
                    'tax_rate' => $po->tax_rate ?? 10,
                    'tax_amount' => $po->tax_amount ?? 0,
                    'grand_total' => $po->grand_total ?? 0,
                    'notes' => $po->notes ?? '',
                    'received' => $po->received ?? false,
                    'items' => collect($po->items ?? [])->map(fn ($i) => [
                        'id' => $i->id,
                        'material_name' => $i->material_name ?? 'N/A',
                        'qty' => $i->qty ?? 0,
                        'unit' => $i->unit ?? 'pcs',
                        'unit_price' => $i->unit_price ?? 0,
                        'total' => $i->total ?? 0,
                    ]),
                ]);

            $invoices = PurchaseInvoice::orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number ?? 'N/A',
                    'po_number' => $inv->po_number ?? 'N/A',
                    'supplier_name' => $inv->supplier_name ?? 'N/A',
                    'invoice_date' => $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') : null,
                    'due_date' => $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('Y-m-d') : null,
                    'amount' => $inv->amount ?? 0,
                    'payment_terms' => $inv->payment_terms ?? 'Net 30',
                    'status' => $inv->status ?? 'unpaid',
                    'received_at' => $inv->received_at ? \Carbon\Carbon::parse($inv->received_at)->format('Y-m-d') : null,
                ]);

            $payments = ProcurementPayment::orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'payment_number' => $p->payment_number ?? 'N/A',
                    'invoice_number' => $p->invoice_number ?? 'N/A',
                    'supplier_name' => $p->supplier_name ?? 'N/A',
                    'paid_date' => $p->paid_date ? \Carbon\Carbon::parse($p->paid_date)->format('Y-m-d') : null,
                    'amount' => $p->amount ?? 0,
                    'method' => $p->method ?? 'Bank Transfer',
                    'bank_reference' => $p->bank_reference ?? '',
                    'remarks' => $p->remarks ?? '',
                    'status' => $p->status ?? 'cleared',
                ]);

            $stats = [
                'pendingMaterialRequests' => $materialRequests->where('status', 'pending')->count(),
                'activeRFQs' => $rfqs->whereIn('status', ['sent', 'partial_response'])->count(),
                'pendingPOs' => $purchaseOrders->whereIn('status', ['draft', 'sent'])->count(),
                'unpaidInvoices' => $invoices->where('status', 'unpaid')->count(),
            ];

            return Inertia::render('Dashboard/SCM/Manager/Procurement', compact(
                'warehouses', 'materialRequests', 'suppliers', 'rfqs',
                'purchaseOrders', 'invoices', 'payments', 'stats'
            ));

        } catch (\Exception $e) {
            Log::error('Procurement page render failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to load procurement data.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE & SEND RFQ
    // ──────────────────────────────────────────────────────────────

    public function createRFQ(Request $request)
    {
        $validated = $request->validate([
            'mr_id' => 'required|exists:material_requests,id',
            'material_name' => 'required|string',
            'required_qty' => 'required|numeric|min:1',
            'unit' => 'required|string',
            'deadline' => 'required|date|after_or_equal:today', // <-- Fixed: Allows today
            'delivery_address' => 'required|string',
            'payment_terms' => 'required|string',
            'notes' => 'nullable|string',
            'selected_suppliers' => 'required|array|min:1',
            'selected_suppliers.*' => 'exists:suppliers,id',
        ]);

        DB::beginTransaction();
        try {
            $mr = MaterialRequest::findOrFail($validated['mr_id']);

            RequestForQuotation::create([
                'rfq_number' => $this->generateRFQNumber(),
                'mr_ref' => $mr->req_number,
                'mr_id' => $mr->id,
                'material_id' => $mr->material_id,
                'material_name' => $validated['material_name'],
                'category' => $mr->category,
                'unit' => $validated['unit'],
                'required_qty' => $validated['required_qty'],
                'deadline' => $validated['deadline'],
                'delivery_address' => $validated['delivery_address'],
                'payment_terms' => $validated['payment_terms'],
                'notes' => $validated['notes'],
                'sent_at' => now(),
                'status' => 'sent',
                'supplier_ids' => json_encode($validated['selected_suppliers']),
            ]);

            $mr->update(['status' => 'rfq_sent']);

            DB::commit();

            return redirect()->back()->with('success', 'RFQ Sent to selected vendors successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RFQ creation failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to create RFQ.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // ACCEPT QUOTATION → CREATE PO
    // ──────────────────────────────────────────────────────────────

    public function acceptQuotation(Request $request, $responseId)
    {
        $validated = $request->validate([
            'rfq_id' => 'required|exists:request_for_quotations,id',
        ]);

        DB::beginTransaction();
        try {
            $response = RfqResponse::with('rfq')->findOrFail($responseId);
            $rfq = $response->rfq;
            $supplier = Supplier::findOrFail($response->supplier_id);

            $response->update(['status' => 'accepted']);

            RfqResponse::where('rfq_id', $rfq->id)
                ->where('id', '!=', $responseId)
                ->where('status', 'pending_review')
                ->update(['status' => 'declined']);

            $subtotal = $response->total_price;
            $taxRate = 10;
            $taxAmount = $subtotal * ($taxRate / 100);
            $grandTotal = $subtotal + $taxAmount;

            $po = ScmPurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->business_name,
                'rfq_ref' => $rfq->rfq_number,
                'rfq_id' => $rfq->id,
                'issued_date' => now(),
                'expected_delivery' => $response->lead_time,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'notes' => '',
                'received' => false,
            ]);

            ScmPurchaseOrderItem::create([
                'scm_purchase_order_id' => $po->id,
                'material_id' => $rfq->material_id,
                'material_name' => $rfq->material_name,
                'qty' => $rfq->required_qty,
                'unit' => $rfq->unit,
                'unit_price' => $response->unit_price,
                'total' => $response->total_price,
            ]);

            $rfq->update(['status' => 'responded']);

            DB::commit();

            return redirect()->back()->with('success', 'Quotation accepted and Purchase Order generated.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accept quotation failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to accept quotation.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // DECLINE QUOTATION
    // ──────────────────────────────────────────────────────────────

    public function declineQuotation(Request $request, $responseId)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $response = RfqResponse::findOrFail($responseId);
            $response->update([
                'status' => 'declined',
                'decline_reason' => $validated['reason'],
            ]);

            return redirect()->back()->with('success', 'Quotation declined.');
        } catch (\Exception $e) {
            Log::error('Decline quotation failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to decline quotation.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // SEND PURCHASE ORDER TO SUPPLIER
    // ──────────────────────────────────────────────────────────────

    public function sendPurchaseOrder($poId)
    {
        try {
            $po = ScmPurchaseOrder::findOrFail($poId);
            $po->update(['status' => 'sent']);

            return redirect()->back()->with('success', 'Purchase Order sent to supplier.');
        } catch (\Exception $e) {
            Log::error('Send PO failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to send PO.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // PROCESS PAYMENT
    // ──────────────────────────────────────────────────────────────

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

            return redirect()->back()->withErrors(['error' => 'Failed to process payment.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // SUPPLIER PORTAL: SUBMIT QUOTATION RESPONSE (API/Fallback)
    // ──────────────────────────────────────────────────────────────

    public function submitQuotationResponse(Request $request, $rfqId)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'lead_time' => 'required|string',
            'validity_date' => 'required|date|after_or_equal:today',
            'payment_terms' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $rfq = RequestForQuotation::findOrFail($rfqId);
            $supplier = Supplier::findOrFail($validated['supplier_id']);

            $response = RfqResponse::create([
                'rfq_id' => $rfq->id,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->business_name,
                'unit_price' => $validated['unit_price'],
                'total_price' => $validated['total_price'],
                'lead_time' => $validated['lead_time'],
                'validity_date' => $validated['validity_date'],
                'payment_terms' => $validated['payment_terms'],
                'notes' => $validated['notes'],
                'status' => 'pending_review',
                'submitted_at' => now(),
            ]);

            $existingResponses = RfqResponse::where('rfq_id', $rfq->id)->count();
            $rfq->update(['status' => $existingResponses >= 2 ? 'responded' : 'partial_response']);

            return response()->json(['success' => true, 'response' => $response]);
        } catch (\Exception $e) {
            Log::error('Submit quotation response failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to submit quotation.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // SUPPLIER PORTAL: SEND PURCHASE INVOICE (API/Fallback)
    // ──────────────────────────────────────────────────────────────

    public function receiveInvoiceFromSupplier(Request $request)
    {
        $validated = $request->validate([
            'po_id' => 'required|exists:scm_purchase_orders,id',
            'invoice_number' => 'required|string|unique:purchase_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'amount' => 'required|numeric|min:0.01',
            'payment_terms' => 'required|string',
        ]);

        try {
            $po = ScmPurchaseOrder::findOrFail($validated['po_id']);

            $invoice = PurchaseInvoice::create([
                'invoice_number' => $validated['invoice_number'],
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'supplier_id' => $po->supplier_id,
                'supplier_name' => $po->supplier_name,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'amount' => $validated['amount'],
                'payment_terms' => $validated['payment_terms'],
                'status' => 'unpaid',
                'received_at' => now(),
            ]);

            return response()->json(['success' => true, 'invoice' => $invoice]);
        } catch (\Exception $e) {
            Log::error('Receive invoice failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to record invoice.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS: Auto-numbering
    // ──────────────────────────────────────────────────────────────

    private function generateRFQNumber(): string
    {
        $year = now()->format('Y');
        $count = RequestForQuotation::whereYear('created_at', $year)->count() + 1;

        return 'RFQ-'.$year.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    private function generatePONumber(): string
    {
        $year = now()->format('Y');
        $count = ScmPurchaseOrder::whereYear('created_at', $year)->count() + 1;

        return 'SCMPO-'.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function generatePaymentNumber(): string
    {
        $year = now()->format('Y');
        $count = ProcurementPayment::whereYear('created_at', $year)->count() + 1;

        return 'PAY-'.$year.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
