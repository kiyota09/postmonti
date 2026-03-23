<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\inv\Material;
use App\Models\inv\Warehouse;
use App\Models\inv\WarehouseMaterial;
use App\Models\Scm\ScmPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class InventoryController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function warehouseColors(): array
    {
        return ['blue', 'emerald', 'amber', 'violet', 'rose', 'cyan'];
    }

    private function buildInventoryData(): array
    {
        $warehouses = Warehouse::all();
        $data = [];

        foreach ($warehouses as $wh) {
            $items = WarehouseMaterial::with('material')
                ->where('warehouse_id', $wh->id)
                ->get()
                ->map(function (WarehouseMaterial $wm) {
                    $mat = $wm->material;
                    $qty = (float) $wm->quantity;
                    $reorder = $mat->reorder_point ?? 0;

                    if ($qty <= 0) {
                        $status = 'Out of Stock';
                    } elseif ($qty <= $reorder) {
                        $status = 'Low Stock';
                    } else {
                        $status = 'In Stock';
                    }

                    return [
                        'wm_id' => $wm->id,
                        'id' => $mat->mat_id,
                        'material_id' => $mat->id,
                        'name' => $mat->name,
                        'category' => $mat->category,
                        'qty' => $qty,
                        'unit' => $mat->unit,
                        'reorder' => $reorder,
                        'cost' => (float) $mat->unit_cost,
                        'status' => $status,
                    ];
                })
                ->values()
                ->toArray();

            $data[$wh->id] = $items;
        }

        return $data;
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function inventory()
    {
        $warehouses = Warehouse::all()->map(fn ($w) => [
            'id' => $w->id,
            'name' => $w->name,
            'location' => $w->location,
            'manager' => $w->manager,
            'color' => $w->color,
        ])->values()->toArray();

        $inventoryData = $this->buildInventoryData();

        $masterMaterials = Material::orderBy('name')->get()->map(fn ($m) => [
            'id' => $m->id,
            'mat_id' => $m->mat_id,
            'name' => $m->name,
            'category' => $m->category,
            'unit' => $m->unit,
            'cost' => (float) $m->unit_cost,
        ])->values()->toArray();

        // FETCH INCOMING DELIVERIES (Bulletproofed material resolution & pending filters)
        $incomingDeliveries = ScmPurchaseOrder::with('items')
            ->whereIn('status', ['shipping', 'delivered', 'partially_received'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($po) {
                $pendingItems = $po->items->map(function ($item) {

                    // 1. Bulletproof Material ID Lookup (Fallback to SKU/Name if missing)
                    $materialId = $item->material_id;
                    if (! $materialId) {
                        $mat = Material::where('mat_id', $item->sku ?? '')
                            ->orWhere('name', $item->name ?? $item->material_name ?? '')
                            ->first();
                        $materialId = $mat ? $mat->id : null;
                    }

                    $received = (float) ($item->received_qty ?? 0);
                    $qty = (float) ($item->qty ?? 0);
                    $pending = max(0, $qty - $received);

                    return [
                        'id' => $item->id,
                        'material_id' => $materialId,
                        'material_name' => $item->name ?? $item->material_name ?? 'Unknown Material',
                        'qty' => $qty, // Originally Ordered
                        'received_qty' => $received, // Total Received Historically
                        'pending_qty' => $pending, // Left to Receive NOW
                        'unit' => $item->unit ?? 'unit',
                    ];
                })->filter(fn ($item) => $item['pending_qty'] > 0 && $item['material_id'])->values();

                return [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier_name' => $po->supplier_name ?? 'Vendor',
                    'status' => $po->status,
                    'items' => $pendingItems,
                ];
            })
            ->filter(fn ($po) => count($po['items']) > 0) // Only send POs that still have pending items
            ->values();

        return Inertia::render('Dashboard/INV/Manager/inventory', [
            'warehouses' => $warehouses,
            'inventoryData' => $inventoryData,
            'masterMaterials' => $masterMaterials,
            'incomingDeliveries' => $incomingDeliveries,
        ]);
    }

    // ── Receive Incoming Deliveries (Bulletproof Transaction) ────────────────────────

    public function receiveDelivery(Request $request)
    {
        try {
            $validated = $request->validate([
                'po_id' => 'required',
                'warehouse_id' => 'required|exists:warehouses,id',
                'items' => 'required|array',
                'items.*.item_id' => 'required',
                'items.*.material_id' => 'required|exists:materials,id',
                'items.*.received_qty' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            $po = ScmPurchaseOrder::with('items')->findOrFail($validated['po_id']);
            $hasReceivedSomething = false;

            foreach ($validated['items'] as $itemData) {
                $rcvd = (float) $itemData['received_qty'];

                if ($rcvd > 0) {
                    $hasReceivedSomething = true;

                    // 1. Add Stock to the Physical Warehouse
                    $wm = WarehouseMaterial::firstOrNew([
                        'warehouse_id' => $validated['warehouse_id'],
                        'material_id' => $itemData['material_id'],
                    ]);
                    $wm->quantity = ($wm->quantity ?? 0) + $rcvd;
                    $wm->save();

                    // 2. Add 'Received Quantity' to the Purchase Order Item History (Schema-safe)
                    $poItem = $po->items->where('id', $itemData['item_id'])->first();
                    if ($poItem) {
                        if (Schema::hasColumn($poItem->getTable(), 'received_qty')) {
                            $poItem->received_qty = ($poItem->received_qty ?? 0) + $rcvd;
                            $poItem->save();
                        }
                    }
                }
            }

            // 3. Determine PO Status safely
            $po->refresh();
            $allFullyReceived = true;

            if (Schema::hasColumn($po->items()->getRelated()->getTable(), 'received_qty')) {
                foreach ($po->items as $item) {
                    if ((float) ($item->received_qty ?? 0) < (float) ($item->qty ?? 0)) {
                        $allFullyReceived = false;
                        break;
                    }
                }
            } else {
                $allFullyReceived = true; // Default to fully received if no tracking column exists
            }

            if ($allFullyReceived) {
                $po->update(['status' => 'delivered']);
            } elseif ($hasReceivedSomething) {
                $po->update(['status' => 'partially_received']);
            }

            DB::commit();

            return redirect()->back()->with('message', 'Delivery received and inventory updated!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Receive delivery failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Processing failed: '.$e->getMessage()]);
        }
    }

    // ── Store Warehouse ───────────────────────────────────────────────────────

    public function storeWarehouse(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'manager' => 'nullable|string|max:255',
        ]);

        $colors = self::warehouseColors();
        $data['color'] = $colors[Warehouse::count() % count($colors)];

        Warehouse::create($data);

        return redirect()->back()->with('message', 'Warehouse created successfully.');
    }

    // ── Add Item to Warehouse ─────────────────────────────────────────────────

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $wm = WarehouseMaterial::firstOrNew([
            'warehouse_id' => $data['warehouse_id'],
            'material_id' => $data['material_id'],
        ]);

        $wm->quantity = ($wm->quantity ?? 0) + (float) $data['quantity'];
        $wm->save();

        return redirect()->back()->with('message', 'Item added to warehouse.');
    }

    // ── Remove Item from Warehouse ────────────────────────────────────────────

    public function destroyItem(int $wmId)
    {
        WarehouseMaterial::findOrFail($wmId)->delete();

        return redirect()->back()->with('message', 'Item removed.');
    }
}
