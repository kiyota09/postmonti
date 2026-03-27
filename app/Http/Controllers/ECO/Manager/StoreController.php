<?php

namespace App\Http\Controllers\ECO\Manager;

use App\Http\Controllers\Controller;
use App\Models\inv\Product as InvProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreController extends Controller
{
    /**
     * Display the product catalog (storefront) for ECO Manager.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // Build query with optional filters
        $query = InvProduct::with(['sizes', 'bom', 'specs', 'images'])
            ->where('status', 'Active');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('product_id', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if ($request->filled('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        // Order products
        $query->orderBy('name');

        // Get products and transform for frontend
        $products = $query->get()->map(function (InvProduct $product) {
            return [
                'id' => $product->id,
                'product_id' => $product->product_id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category,
                'subcategory' => $product->subcategory,
                'status' => $product->status,
                'color_tag' => $product->color_tag,
                'colorHex' => $product->color_hex,
                'colorName' => $product->color_name,
                'weight' => $product->weight,
                'dimensions' => $product->dimensions,
                'batch_size' => $product->batch_size,
                'leadTime' => $product->lead_time,
                'unitCost' => (float) $product->unit_cost,
                'sellingPrice' => (float) $product->selling_price,
                'stockOnHand' => $product->stock_on_hand,
                'moq' => $product->moq,
                'certification' => $product->certification,
                'description' => $product->description,
                'sizes' => $product->sizes->pluck('size')->toArray(),
                'materials' => $product->bom->map(fn ($b) => [
                    'sku' => $b->sku_ref,
                    'name' => $b->name,
                    'qty' => (float) $b->qty,
                    'unit' => $b->unit,
                    'category' => $b->category,
                    'warehouse' => $b->warehouse_note,
                    'cost' => (float) $b->unit_cost,
                    'stockStatus' => $b->stock_status,
                ])->toArray(),
                'specs' => $product->specs->map(fn ($s) => [
                    'label' => $s->label,
                    'value' => $s->value,
                ])->toArray(),
                'images' => $product->images->sortBy('sort_order')->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/'.$img->path),
                ])->values()->toArray(),
            ];
        })->values();

        return Inertia::render('Dashboard/ECO/Manager/store', [
            'products' => $products,
            'filters' => $request->only(['search', 'category']),
        ]);
    }
}
