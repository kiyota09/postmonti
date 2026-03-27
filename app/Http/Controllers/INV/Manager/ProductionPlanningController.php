<?php

namespace App\Http\Controllers\INV\Manager;

use App\Http\Controllers\Controller;
use App\Models\inv\Material;
use App\Models\inv\WarehouseMaterial;
use App\Models\PurchaseOrder;
use App\Models\Scm\MaterialRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionPlanningController extends Controller
{
    /**
     * Display orders that are awaiting material availability check (stage = inv_check).
     */
    public function index()
    {
        $orders = PurchaseOrder::with(['client', 'items.product'])
            ->whereHas('queue', function ($q) {
                $q->where('stage', 'inv_check');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // For each order, calculate material requirements based on BOM
        $orders->each(function ($order) {
            $materialNeeds = [];
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product && $product->bom) {
                    foreach ($product->bom as $bom) {
                        $materialNeeds[$bom->material_id] = ($materialNeeds[$bom->material_id] ?? 0) + ($bom->qty * $item->quantity);
                    }
                }
            }
            $order->material_needs = $materialNeeds;
        });

        return Inertia::render('Dashboard/INV/Manager/ProductionPlanning', [
            'orders' => $orders,
        ]);
    }

    /**
     * Check material availability for a specific order.
     * Updates the queue stage to 'inv_checked' and records whether materials are sufficient.
     * If insufficient, creates material requests for missing items.
     * SCM will then decide the next step based on the sufficiency flag.
     */
    public function checkAvailability(PurchaseOrder $order)
    {
        $insufficient = [];
        $totalQty = [];
        $productQtys = [];

        // Calculate total material needs from BOM of each ordered product
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product && $product->bom) {
                foreach ($product->bom as $bom) {
                    $need = $bom->qty * $item->quantity;
                    $totalQty[$bom->material_id] = ($totalQty[$bom->material_id] ?? 0) + $need;
                    $productQtys[$bom->material_id] = $productQtys[$bom->material_id] ?? [];
                    $productQtys[$bom->material_id][] = ['product' => $product->name, 'need' => $need];
                }
            }
        }

        // Check stock availability for each required material
        foreach ($totalQty as $materialId => $need) {
            $material = Material::find($materialId);
            $stock = WarehouseMaterial::where('material_id', $materialId)->sum('quantity');
            if ($stock < $need) {
                $insufficient[] = [
                    'material_id' => $materialId,
                    'material' => $material->name,
                    'category' => $material->category,
                    'unit' => $material->unit,
                    'stock' => $stock,
                    'need' => $need,
                    'deficit' => $need - $stock,
                ];
            }
        }

        $queue = $order->queue;

        if (empty($insufficient)) {
            // Sufficient materials: set flag to true
            $queue->update([
                'stage' => 'inv_checked',
                'inv_checked_at' => now(),
                'inv_check_sufficient' => true,
            ]);

            return redirect()->route('inv.manager.production-planning')
                ->with('success', 'Materials sufficient. Order is now pending SCM approval for manufacturing.');
        } else {
            // Insufficient materials: create material requests and set flag to false
            foreach ($insufficient as $mat) {
                // Generate request number
                $year = now()->format('Y');
                $count = MaterialRequest::whereYear('created_at', $year)->count() + 1;
                $reqNumber = 'MR-'.$year.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);

                MaterialRequest::create([
                    'req_number' => $reqNumber,
                    'material_id' => $mat['material_id'],
                    'material_name' => $mat['material'],
                    'category' => $mat['category'],
                    'unit' => $mat['unit'],
                    'current_stock' => $mat['stock'],
                    'reorder_point' => 0,
                    'required_qty' => $mat['deficit'],
                    'urgency' => 'High',
                    'requested_by' => 'Production Planning (Order #'.$order->po_number.')',
                    'requested_at' => now(),
                    'status' => 'pending',
                    'notes' => 'Created automatically from order #'.$order->po_number,
                ]);
            }

            $queue->update([
                'stage' => 'inv_checked',
                'inv_checked_at' => now(),
                'inv_check_sufficient' => false,
            ]);

            return redirect()->route('inv.manager.production-planning')
                ->with('insufficient', $insufficient)
                ->with('success', 'Material requests created. SCM will be notified.');
        }
    }
}
