<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Distribution;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributionController extends Controller
{
    public function index()
    {
        return Distribution::with('department', 'request', 'items.product')->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'integer'],
            'internal_request_id' => ['nullable', 'integer'],
            'status' => ['required', 'string', 'max:50'],
            'delivered_at' => ['nullable', 'date'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $distribution = Distribution::create([
                'distribution_number' => 'DIST-' . now()->format('YmdHis'),
                'department_id' => $data['department_id'] ?? null,
                'internal_request_id' => $data['internal_request_id'] ?? null,
                'delivered_by' => $request->user()?->id,
                'status' => $data['status'],
                'delivered_at' => $data['delivered_at'] ?? null,
                'received_by' => $data['received_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuficiente para {$product->name}."],
                    ]);
                }

                $product->decrement('quantity', $item['quantity']);
                $product->refresh();

                $distribution->items()->create($item);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'type' => 'saida',
                    'quantity' => $item['quantity'],
                    'balance_after' => $product->quantity,
                    'reference' => $distribution->distribution_number,
                    'notes' => 'Saida por distribuicao logistica.',
                ]);
            }

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'action' => 'Criou distribuicao',
                'entity' => 'Distribution',
                'entity_id' => $distribution->id,
                'metadata' => ['distribution_number' => $distribution->distribution_number],
            ]);

            return $distribution->load('department', 'request', 'items.product');
        });
    }

    public function show(Distribution $distribution)
    {
        return $distribution->load('department', 'request', 'items.product');
    }

    public function update(Request $request, Distribution $distribution)
    {
        $distribution->update($request->validate([
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'delivered_at' => ['nullable', 'date'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return $distribution->load('department', 'request', 'items.product');
    }

    public function destroy(Distribution $distribution)
    {
        $distribution->delete();

        return response()->noContent();
    }
}
