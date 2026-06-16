<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with('category', 'supplier')->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        return Product::create($request->validate([
            'product_category_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku'],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['integer', 'min:0'],
            'minimum_quantity' => ['integer', 'min:0'],
            'estimated_unit_cost' => ['numeric', 'min:0'],
            'active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]));
    }

    public function show(Product $product)
    {
        return $product->load('category', 'supplier');
    }

    public function update(Request $request, Product $product)
    {
        $product->update($request->validate([
            'product_category_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'required', 'string', 'max:80', 'unique:products,sku,' . $product->id],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'quantity' => ['integer', 'min:0'],
            'minimum_quantity' => ['integer', 'min:0'],
            'estimated_unit_cost' => ['numeric', 'min:0'],
            'active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]));

        return $product->load('category', 'supplier');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->noContent();
    }
}
