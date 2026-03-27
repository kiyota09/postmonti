<?php

namespace App\Http\Controllers\INV;

use App\Http\Controllers\Controller;
use App\Models\INV\Material;
use App\Models\INV\Warehouse;
use App\Models\INV\WarehouseMaterial;
use App\Models\SCM\MaterialRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    // ── Page ──────────────────────────────────────────────────────────────────

    public function material()
    {
        $warehouses = Warehouse::orderBy('id')->get()->map(fn ($w) => [
            'id' => $w->id,
            'name' => $w->name,
            'location' => $w->location,
            'color' => $w->color,
        ])->values()->toArray();

        $whIds = collect($warehouses)->pluck('id');

        $materials = Material::with(['warehouseMaterials.warehouse'])
            ->orderBy('name')
            ->get()
            ->map(function (Material $mat) use ($whIds) {
                // Build stock keyed by warehouse id, filling 0 for warehouses with no record
                $stock = [];
                foreach ($whIds as $wid) {
                    $stock[(string) $wid] = 0;
                }
                foreach ($mat->warehouseMaterials as $wm) {
                    $stock[(string) $wm->warehouse_id] = (float) $wm->quantity;
                }

                $totalQty = array_sum($stock);
                $reorder = $mat->reorder_point;

                if ($totalQty <= 0) {
                    $status = 'Out of Stock';
                } elseif ($totalQty <= $reorder) {
                    $status = 'Low Stock';
                } else {
                    $status = 'In Stock';
                }

                return [
                    'id' => $mat->id,
                    'mat_id' => $mat->mat_id,
                    'name' => $mat->name,
                    'category' => $mat->category,
                    'unit' => $mat->unit,
                    'reorder' => $reorder,
                    'cost' => (float) $mat->unit_cost,
                    'stock' => $stock,
                    'status' => $status,
                ];
            })
            ->values()
            ->toArray();

        // FETCH PROCUREMENT REQUEST HISTORY TO SEND TO VUE
        $materialRequests = MaterialRequest::orderBy('created_at', 'desc')->get();

        return Inertia::render('Dashboard/INV/Manager/Material', [
            'warehouses' => $warehouses,
            'materials' => $materials,
            'materialRequests' => $materialRequests, // Added this payload
        ]);
    }

    // ── Store Material ────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'quantity' => 'nullable|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'reorder_point' => 'nullable|integer|min:0',
        ]);

        $material = Material::create([
            'mat_id' => Material::nextMatId(),
            'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'],
            'unit_cost' => $data['unit_cost'],
            'reorder_point' => $data['reorder_point'] ?? 0,
        ]);

        // If initial quantity provided, assign to main warehouse (first warehouse)
        $qty = (float) ($data['quantity'] ?? 0);
        if ($qty > 0) {
            $mainWarehouse = Warehouse::orderBy('id')->first();
            if ($mainWarehouse) {
                WarehouseMaterial::create([
                    'warehouse_id' => $mainWarehouse->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Material added to master list.');
    }

    // ── Delete Material ───────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        Material::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Material deleted.');
    }

    // ── Delegate (Transfer Stock) ─────────────────────────────────────────────

    public function delegate(Request $request)
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'from_warehouse' => 'required|exists:warehouses,id',
            'to_warehouse' => 'required|exists:warehouses,id|different:from_warehouse',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $qty = (float) $data['quantity'];

        // Lock and check source stock
        $from = WarehouseMaterial::where('warehouse_id', $data['from_warehouse'])
            ->where('material_id', $data['material_id'])
            ->lockForUpdate()
            ->first();

        if (! $from || $from->quantity < $qty) {
            return redirect()->back()->withErrors(['quantity' => 'Insufficient stock in source warehouse.']);
        }

        $from->decrement('quantity', $qty);

        // Upsert destination
        $to = WarehouseMaterial::firstOrNew([
            'warehouse_id' => $data['to_warehouse'],
            'material_id' => $data['material_id'],
        ]);
        $to->quantity = ($to->quantity ?? 0) + $qty;
        $to->save();

        return redirect()->back()->with('success', 'Stock delegated successfully.');
    }

    // ── Request Procurement (Send to SCM) ────────────────────────────────────

    public function requestProcurement(Request $request)
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'material_name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
            'required_qty' => 'required|numeric|min:1',
            'urgency' => 'required|in:High,Medium,Low',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Generate request number: MR-YYYY-NNN
        $year = now()->year;
        $count = MaterialRequest::whereYear('created_at', $year)->count() + 1;
        $reqNumber = 'MR-'.$year.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);

        MaterialRequest::create([
            'req_number' => $reqNumber,
            'material_id' => $data['material_id'],
            'material_name' => $data['material_name'],
            'category' => $data['category'],
            'unit' => $data['unit'],
            'current_stock' => $data['current_stock'],
            'reorder_point' => $data['reorder_point'],
            'required_qty' => $data['required_qty'],
            'urgency' => $data['urgency'],
            'requested_by' => 'Inventory Management',
            'requested_at' => now()->toDateString(),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', "Procurement request {$reqNumber} sent to SCM successfully.");
    }
}
