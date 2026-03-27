<?php

namespace App\Http\Controllers\ECO\Staff;

use App\Http\Controllers\Controller;
use App\Models\inv\Product as InvProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function products(Request $request)
    {
        $invProducts = InvProduct::with(['sizes', 'bom', 'specs', 'images'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('product_id', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->get()
            ->map(function (InvProduct $p) {
                return [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'category' => $p->category,
                    'subcategory' => $p->subcategory,
                    'status' => $p->status,
                    'color_tag' => $p->color_tag,
                    'colorHex' => $p->color_hex,
                    'colorName' => $p->color_name,
                    'weight' => $p->weight,
                    'dimensions' => $p->dimensions,
                    'batch_size' => $p->batch_size,
                    'leadTime' => $p->lead_time,
                    'unitCost' => (float) $p->unit_cost,
                    'sellingPrice' => (float) $p->selling_price,
                    'stockOnHand' => $p->stock_on_hand,
                    'moq' => $p->moq,
                    'certification' => $p->certification,
                    'description' => $p->description,
                    'sizes' => $p->sizes->pluck('size')->toArray(),
                    'materials' => $p->bom->map(fn ($b) => [
                        'sku' => $b->sku_ref,
                        'name' => $b->name,
                        'qty' => (float) $b->qty,
                        'unit' => $b->unit,
                        'category' => $b->category,
                        'warehouse' => $b->warehouse_note,
                        'cost' => (float) $b->unit_cost,
                        'stockStatus' => $b->stock_status,
                    ])->toArray(),
                    'specs' => $p->specs->map(fn ($s) => [
                        'label' => $s->label,
                        'value' => $s->value,
                    ])->toArray(),
                    'images' => $p->images->sortBy('sort_order')->map(fn ($img) => [
                        'id' => $img->id,
                        'url' => asset('storage/'.$img->path),
                    ])->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Dashboard/ECO/Employee/Products', [
            'invProducts' => $invProducts,
            'filters' => $request->only(['search']),
        ]);
    }
}
