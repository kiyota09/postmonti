<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\INV\Product as InvProduct;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display the product catalog for the authenticated client.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $client = Auth::guard('client')->user();

        $products = InvProduct::where('status', 'Active')
            ->with(['sizes', 'images'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'category' => $p->category,
                    'subcategory' => $p->subcategory,
                    'description' => $p->description,
                    'unit_cost' => $p->unit_cost,
                    'selling_price' => $p->selling_price,
                    'stock_on_hand' => $p->stock_on_hand,
                    'unit' => 'pcs',
                    'sizes' => $p->sizes->pluck('size')->toArray(),
                    'images' => $p->images->sortBy('sort_order')->map(fn ($img) => asset('storage/'.$img->path))->values(),
                ];
            });

        return Inertia::render('Client/Products', [
            'products' => $products,
            'client' => $client,
        ]);
    }
}
