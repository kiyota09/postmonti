<?php

namespace App\Http\Controllers\MAN\Manager;

use App\Http\Controllers\Controller;
use App\Models\FormJob;
use App\Models\Machine;
use App\Models\ManufacturingOrder;
use App\Models\Package;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ManufacturingManagerController extends Controller
{
    /**
     * Display the manufacturing manager dashboard.
     */
    public function index()
    {
        // Statistics
        $receivedOrders = PurchaseOrder::whereHas('queue', function ($q) {
            $q->where('stage', 'man_production');
        })->count();

        $inProduction = ManufacturingOrder::where('status', 'in_progress')->count();

        $activeMachines = Machine::where('status', 'available')->count();

        // All manufacturing staff (users with role MAN and position staff)
        $staff = User::where('role', 'MAN')
            ->where('position', 'staff')
            ->select('id', 'name', 'email', 'manufacturing_role')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'manufacturing_role' => $user->manufacturing_role,
                ];
            });

        return Inertia::render('Dashboard/MAN/Manager/Index', [
            'stats' => [
                'receivedOrders' => $receivedOrders,
                'inProduction' => $inProduction,
                'activeMachines' => $activeMachines,
            ],
            'staff' => $staff,
        ]);
    }

    /**
     * Show all orders received from SCM that are ready for production.
     */
    public function production()
    {
        $orders = PurchaseOrder::with(['client', 'items.product'])
            ->whereHas('queue', function ($q) {
                $q->where('stage', 'man_production');
            })
            ->get()
            ->map(function ($order) {
                // Aggregate product details
                $items = $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->product->name,
                        'product_sku' => $item->product->sku,
                        'quantity' => $item->quantity,
                    ];
                });

                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client_name' => $order->client->company_name,
                    'total_quantity' => $order->items->sum('quantity'),
                    'items' => $items,
                    'created_at' => $order->created_at,
                ];
            });

        return Inertia::render('Dashboard/MAN/Manager/Production', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show all rejected items (forms that were rejected).
     */
    public function rejected()
    {
        $rejectedForms = FormJob::where('status', 'rejected')
            ->with(['ironJob.squeezerJob.softenerJob.fabric', 'product', 'operator'])
            ->latest()
            ->get()
            ->map(function ($form) {
                return [
                    'id' => $form->id,
                    'code' => $form->code,
                    'product_name' => $form->product->name,
                    'quantity' => $form->quantity,
                    'rejected_at' => $form->updated_at,
                    'rejected_by' => $form->operator->name,
                    'fabric_code' => $form->ironJob->squeezerJob->softenerJob->fabric->code ?? null,
                    'reason' => $form->remarks ?? 'No reason provided',
                ];
            });

        return Inertia::render('Dashboard/MAN/Manager/Rejected', [
            'rejectedItems' => $rejectedForms,
        ]);
    }

    /**
     * Forward a purchase order to the checker department.
     */
    public function forwardToChecker($orderId)
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Create manufacturing order record
        $totalQuantity = $order->items->sum('quantity');
        $manufacturingOrder = ManufacturingOrder::create([
            'purchase_order_id' => $order->id,
            'total_quantity' => $totalQuantity,
            'remaining_quantity' => $totalQuantity,
            'status' => 'pending',
            'notes' => 'Forwarded to manufacturing by '.Auth::user()->name,
        ]);

        // Update order queue stage (optional, but good for tracking)
        $order->queue()->update(['stage' => 'man_production']);

        return redirect()->back()->with('message', 'Order forwarded to production successfully.');
    }

    /**
     * Update a staff member's manufacturing role.
     */
    public function updateStaffRole(Request $request, $staffId)
    {
        $validated = $request->validate([
            'manufacturing_role' => 'required|in:knitting_yarn,dyeing_color,dyeing_fabric_softener,dyeing_squeezer,dyeing_ironing,dyeing_forming,dyeing_packaging,maintenance_checker,checker_quality',
        ]);

        $staff = User::findOrFail($staffId);
        $staff->update(['manufacturing_role' => $validated['manufacturing_role']]);

        return redirect()->back()->with('message', "Staff role updated to {$validated['manufacturing_role']}.");
    }

    /**
     * Mark a package as sent to logistics.
     */
    public function sendToLogistics($packageId)
    {
        $package = Package::findOrFail($packageId);
        $package->update(['status' => 'delivered']);

        // Optionally, update the associated manufacturing order status
        if ($package->manufacturing_order_id) {
            $order = $package->manufacturingOrder;
            $remaining = $order->remaining_quantity - $package->items->sum('quantity');
            $order->update(['remaining_quantity' => $remaining]);
            if ($remaining <= 0) {
                $order->update(['status' => 'completed']);
            }
        }

        return redirect()->back()->with('message', 'Package sent to logistics.');
    }
}
