<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\inv\Material;
use App\Models\inv\Product;
use App\Models\inv\ProductBom;
use App\Models\inv\ProductImage;
use App\Models\inv\ProductSize;
use App\Models\inv\ProductSpec;
use App\Models\inv\Warehouse;
use App\Models\inv\WarehouseMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    // ── Page ──────────────────────────────────────────────────────────────────

    public function product()
    {
        $products = Product::with(['sizes', 'bom', 'specs', 'images'])
            ->orderBy('id')
            ->get()
            ->map(function (Product $p) {
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
                    'grossMargin' => $p->gross_margin,
                    'stockOnHand' => $p->stock_on_hand,
                    'moq' => $p->moq,
                    'certification' => $p->certification,
                    'description' => $p->description,
                    'sizes' => $p->sizes->pluck('size')->toArray(),
                    'materials' => $p->bom->map(fn ($b) => [
                        'id' => $b->id,
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

        $masterMaterials = Material::orderBy('name')->get()->map(fn ($m) => [
            'id' => $m->id,
            'mat_id' => $m->mat_id,
            'name' => $m->name,
            'category' => $m->category,
            'unit' => $m->unit,
            'cost' => (float) $m->unit_cost,
        ])->values()->toArray();

        $warehouses = Warehouse::orderBy('id')->get()->map(fn ($w) => [
            'id' => $w->id,
            'name' => $w->name,
        ])->values()->toArray();

        return Inertia::render('Dashboard/INV/Manager/product', [
            'products' => $products,
            'masterMaterials' => $masterMaterials,
            'warehouses' => $warehouses,
        ]);
    }

    // ── Store Product ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'status' => 'required|in:Active,Draft,Inactive',
            'color_tag' => 'nullable|string|max:50',
            'color_hex' => 'nullable|string|max:20',
            'color_name' => 'nullable|string|max:100',
            'weight' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'batch_size' => 'nullable|integer|min:1',
            'lead_time' => 'nullable|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_on_hand' => 'nullable|integer|min:0',
            'moq' => 'nullable|integer|min:1',
            'certification' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|max:20',
            'bom' => 'nullable|array',
            'bom.*.material_id' => 'nullable|exists:materials,id',
            'bom.*.sku_ref' => 'nullable|string',
            'bom.*.name' => 'required_with:bom|string',
            'bom.*.qty' => 'required_with:bom|numeric|min:0.0001',
            'bom.*.unit' => 'required_with:bom|string',
            'bom.*.category' => 'nullable|string',
            'bom.*.warehouse_note' => 'nullable|string',
            'bom.*.unit_cost' => 'nullable|numeric|min:0',
            'bom.*.stock_status' => 'nullable|string',
            'specs' => 'nullable|array',
            'specs.*.label' => 'required_with:specs|string',
            'specs.*.value' => 'required_with:specs|string',
            'images' => 'nullable|array',
            'images.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $productId = Product::nextProductId();
        $skuBase = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $data['name']), 0, 3));
        $sku = $skuBase.'-'.substr(str_replace('-', '', $productId), 3);

        $product = Product::create([
            'product_id' => $productId,
            'sku' => $sku,
            'name' => $data['name'],
            'category' => $data['category'],
            'subcategory' => $data['subcategory'] ?? null,
            'status' => $data['status'],
            'color_tag' => $data['color_tag'] ?? null,
            'color_hex' => $data['color_hex'] ?? null,
            'color_name' => $data['color_name'] ?? null,
            'weight' => $data['weight'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
            'batch_size' => $data['batch_size'] ?? null,
            'lead_time' => $data['lead_time'] ?? null,
            'unit_cost' => $data['unit_cost'],
            'selling_price' => $data['selling_price'],
            'stock_on_hand' => $data['stock_on_hand'] ?? 0,
            'moq' => $data['moq'] ?? null,
            'certification' => $data['certification'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        foreach (($data['sizes'] ?? []) as $i => $size) {
            ProductSize::create(['product_id' => $product->id, 'size' => $size, 'sort_order' => $i]);
        }

        foreach (($data['bom'] ?? []) as $i => $line) {
            $mat = isset($line['material_id']) ? Material::find($line['material_id']) : null;
            $unitCost = $mat ? (float) $mat->unit_cost : (float) ($line['unit_cost'] ?? 0);
            $stockStatus = 'In Stock';
            if ($mat) {
                $totalQty = WarehouseMaterial::where('material_id', $mat->id)->sum('quantity');
                if ($totalQty <= 0) {
                    $stockStatus = 'Out of Stock';
                } elseif ($totalQty <= $mat->reorder_point) {
                    $stockStatus = 'Low Stock';
                }
            }
            ProductBom::create([
                'product_id' => $product->id,
                'material_id' => $mat?->id,
                'sku_ref' => $mat ? $mat->mat_id : ($line['sku_ref'] ?? null),
                'name' => $line['name'],
                'qty' => $line['qty'],
                'unit' => $line['unit'],
                'category' => $line['category'] ?? ($mat?->category),
                'warehouse_note' => $line['warehouse_note'] ?? null,
                'unit_cost' => $unitCost,
                'stock_status' => $stockStatus,
                'sort_order' => $i,
            ]);
        }

        foreach (($data['specs'] ?? []) as $i => $spec) {
            ProductSpec::create([
                'product_id' => $product->id,
                'label' => $spec['label'],
                'value' => $spec['value'],
                'sort_order' => $i,
            ]);
        }

        // Store uploaded images
        foreach ($request->file('images', []) as $i => $file) {
            $path = $file->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $i,
            ]);
        }

        return redirect()->back()->with('success', 'Product created successfully.');
    }

    // ── Update Product ────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'status' => 'required|in:Active,Draft,Inactive',
            'color_tag' => 'nullable|string|max:50',
            'color_hex' => 'nullable|string|max:20',
            'color_name' => 'nullable|string|max:100',
            'weight' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'batch_size' => 'nullable|integer|min:1',
            'lead_time' => 'nullable|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_on_hand' => 'nullable|integer|min:0',
            'moq' => 'nullable|integer|min:1',
            'certification' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|max:20',
            'bom' => 'nullable|array',
            'bom.*.material_id' => 'nullable|exists:materials,id',
            'bom.*.sku_ref' => 'nullable|string',
            'bom.*.name' => 'required_with:bom|string',
            'bom.*.qty' => 'required_with:bom|numeric|min:0.0001',
            'bom.*.unit' => 'required_with:bom|string',
            'bom.*.category' => 'nullable|string',
            'bom.*.warehouse_note' => 'nullable|string',
            'bom.*.unit_cost' => 'nullable|numeric|min:0',
            'bom.*.stock_status' => 'nullable|string',
            'specs' => 'nullable|array',
            'specs.*.label' => 'required_with:specs|string',
            'specs.*.value' => 'required_with:specs|string',
            'new_images' => 'nullable|array',
            'new_images.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $product->update([
            'name' => $data['name'],
            'category' => $data['category'],
            'subcategory' => $data['subcategory'] ?? null,
            'status' => $data['status'],
            'color_tag' => $data['color_tag'] ?? null,
            'color_hex' => $data['color_hex'] ?? null,
            'color_name' => $data['color_name'] ?? null,
            'weight' => $data['weight'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
            'batch_size' => $data['batch_size'] ?? null,
            'lead_time' => $data['lead_time'] ?? null,
            'unit_cost' => $data['unit_cost'],
            'selling_price' => $data['selling_price'],
            'stock_on_hand' => $data['stock_on_hand'] ?? 0,
            'moq' => $data['moq'] ?? null,
            'certification' => $data['certification'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        // Re-sync sizes (delete and recreate)
        $product->sizes()->delete();
        foreach (($data['sizes'] ?? []) as $i => $size) {
            ProductSize::create(['product_id' => $product->id, 'size' => $size, 'sort_order' => $i]);
        }

        // Re-sync BOM
        $product->bom()->delete();
        foreach (($data['bom'] ?? []) as $i => $line) {
            $mat = isset($line['material_id']) ? Material::find($line['material_id']) : null;
            $unitCost = $mat ? (float) $mat->unit_cost : (float) ($line['unit_cost'] ?? 0);
            $stockStatus = 'In Stock';
            if ($mat) {
                $totalQty = WarehouseMaterial::where('material_id', $mat->id)->sum('quantity');
                if ($totalQty <= 0) {
                    $stockStatus = 'Out of Stock';
                } elseif ($totalQty <= $mat->reorder_point) {
                    $stockStatus = 'Low Stock';
                }
            }
            ProductBom::create([
                'product_id' => $product->id,
                'material_id' => $mat?->id,
                'sku_ref' => $mat ? $mat->mat_id : ($line['sku_ref'] ?? null),
                'name' => $line['name'],
                'qty' => $line['qty'],
                'unit' => $line['unit'],
                'category' => $line['category'] ?? ($mat?->category),
                'warehouse_note' => $line['warehouse_note'] ?? null,
                'unit_cost' => $unitCost,
                'stock_status' => $stockStatus,
                'sort_order' => $i,
            ]);
        }

        // Re-sync specs
        $product->specs()->delete();
        foreach (($data['specs'] ?? []) as $i => $spec) {
            ProductSpec::create([
                'product_id' => $product->id,
                'label' => $spec['label'],
                'value' => $spec['value'],
                'sort_order' => $i,
            ]);
        }

        // Append new uploaded images (existing ones untouched unless deleted separately)
        $existingCount = $product->images()->count();
        foreach ($request->file('new_images', []) as $i => $file) {
            $path = $file->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $existingCount + $i,
            ]);
        }

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    // ── Delete Single Image ───────────────────────────────────────────────────

    public function destroyImage(int $imageId)
    {
        $image = ProductImage::findOrFail($imageId);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()->back()->with('success', 'Image deleted.');
    }

    // ── Delete Product ────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        // Remove image files from disk
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->path);
        }

        $product->delete();

        return redirect()->back()->with('success', 'Product deleted.');
    }
}
