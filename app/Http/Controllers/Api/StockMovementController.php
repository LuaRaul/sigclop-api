<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function index()
    {
        return StockMovement::with('product')->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'in:entrada,saida,ajuste'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $quantity = (int) $data['quantity'];

            if ($data['type'] === 'saida' && $product->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Quantidade insuficiente em stock.'],
                ]);
            }

            $product->quantity = match ($data['type']) {
                'entrada' => $product->quantity + $quantity,
                'saida' => $product->quantity - $quantity,
                default => $quantity,
            };
            $product->save();

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $request->user()?->id,
                'type' => $data['type'],
                'quantity' => $quantity,
                'balance_after' => $product->quantity,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'action' => 'Registou movimento de stock',
                'entity' => 'StockMovement',
                'entity_id' => $movement->id,
                'metadata' => ['type' => $movement->type, 'product' => $product->name, 'quantity' => $quantity],
            ]);

            return $movement->load('product');
        });
    }

    public function show(StockMovement $stockMovement)
    {
        return $stockMovement->load('product');
    }

    public function update(Request $request, StockMovement $stockMovement)
    {
        return response()->json(['message' => 'Movimentos de stock nao devem ser alterados.'], 405);
    }

    public function destroy(StockMovement $stockMovement)
    {
        return response()->json(['message' => 'Movimentos de stock nao devem ser apagados.'], 405);
    }
}
