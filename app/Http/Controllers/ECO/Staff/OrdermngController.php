<?php

namespace App\Http\Controllers\ECO\Staff;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrdermngController extends Controller
{
    /**
     * Display the Order Architect monitoring dashboard for Staff.
     */
    public function ordermng(Request $request)
    {
        return Inertia::render('Dashboard/ECO/Employee/Ordermng', [
            'orders' => PurchaseOrder::with(['client', 'items.product'])
                ->when($request->search, function ($query, $search) {
                    $query->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString(),

            'filters' => $request->only(['search']),
        ]);
    }
}
