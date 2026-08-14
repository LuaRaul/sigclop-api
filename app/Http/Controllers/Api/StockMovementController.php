<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class StockMovementController extends Controller
{
    /**
     * Lista movimentos de stock.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->can('movimentacoes.visualizar'),
            403,
            'Não possui permissão para visualizar movimentos de stock.'
        );

        $query = StockMovement::query()
            ->with([
                'product',
                'user',
            ]);

        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('product_id')) {
            $query->where(
                'product_id',
                $request->product_id
            );
        }

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->type
            );
        }

        if ($request->filled('reference')) {
            $query->where(
                'reference',
                'like',
                '%' . $request->reference . '%'
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'reference',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'notes',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'product',
                        function ($productQuery) use ($search) {

                            $productQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Ordenação
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'type',
            'quantity',
            'balance_after',
            'reference',
            'created_at',
        ];

        $sortBy = $request->get(
            'sort_by',
            'created_at'
        );

        $sortDirection = strtolower(
            $request->get(
                'sort_direction',
                'desc'
            )
        );

        if (!in_array(
            $sortBy,
            $allowedSorts,
            true
        )) {
            $sortBy = 'created_at';
        }

        if (!in_array(
            $sortDirection,
            ['asc', 'desc'],
            true
        )) {
            $sortDirection = 'desc';
        }

        $query->orderBy(
            $sortBy,
            $sortDirection
        );

        /*
        |--------------------------------------------------------------------------
        | Paginação
        |--------------------------------------------------------------------------
        */

        return $query->paginate(
            min(
                max(
                    $request->integer(
                        'per_page',
                        20
                    ),
                    1
                ),
                100
            )
        );
    }

    /**
     * Regista um novo movimento de stock.
     *
     * Tipos:
     *
     * entrada
     * saida
     * ajuste
     */
    public function store(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->can('movimentacoes.registar'),
            403,
            'Não possui permissão para registar movimentos de stock.'
        );

        $data = $request->validate([

            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'type' => [
                'required',
                Rule::in([
                    'entrada',
                    'saida',
                    'ajuste',
                ]),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:120',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Permission específica por operação
        |--------------------------------------------------------------------------
        */

        $requiredPermission = match (
            $data['type']
        ) {

            'entrada' =>
                'armazem.entrada_stock',

            'saida' =>
                'armazem.saida_stock',

            'ajuste' =>
                'armazem.ajustar_stock',

        };

        abort_if(
            !$user->can($requiredPermission),
            403,
            match ($data['type']) {

                'entrada' =>
                    'Não possui permissão para efectuar entradas de stock.',

                'saida' =>
                    'Não possui permissão para efectuar saídas de stock.',

                'ajuste' =>
                    'Não possui permissão para efectuar ajustes de stock.',

            }
        );

        /*
        |--------------------------------------------------------------------------
        | Transacção
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $data,
            $user
        ) {

            /*
            |--------------------------------------------------------------------------
            | Bloquear produto
            |--------------------------------------------------------------------------
            |
            | Evita que duas operações concorrentes alterem o mesmo stock
            | simultaneamente.
            |
            */

            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail(
                    $data['product_id']
                );

            $quantityBefore =
                (int) $product->quantity;

            $quantity =
                (int) $data['quantity'];

            /*
            |--------------------------------------------------------------------------
            | Calcular novo saldo
            |--------------------------------------------------------------------------
            */

            switch ($data['type']) {

                case 'entrada':

                    $newBalance =
                        $quantityBefore + $quantity;

                    break;

                case 'saida':

                    if (
                        $quantityBefore < $quantity
                    ) {

                        throw ValidationException::withMessages([

                            'quantity' => [

                                "Quantidade insuficiente em stock. "
                                . "Disponível: {$quantityBefore}. "
                                . "Solicitado: {$quantity}.",

                            ],

                        ]);
                    }

                    $newBalance =
                        $quantityBefore - $quantity;

                    break;

                case 'ajuste':

                    /*
                    | No ajuste, quantity representa o novo saldo
                    | pretendido.
                    */

                    $newBalance =
                        $quantity;

                    break;

                default:

                    throw ValidationException::withMessages([

                        'type' => [
                            'Tipo de movimento inválido.',
                        ],

                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Actualizar stock
            |--------------------------------------------------------------------------
            */

            $product->quantity =
                $newBalance;

            $product->save();

            /*
            |--------------------------------------------------------------------------
            | Criar movimento
            |--------------------------------------------------------------------------
            */

            $movement = StockMovement::create([

                'product_id' =>
                    $product->id,

                'user_id' =>
                    $user->id,

                'type' =>
                    $data['type'],

                'quantity' =>
                    $quantity,

                'balance_after' =>
                    $newBalance,

                'reference' =>
                    $data['reference']
                    ?? null,

                'notes' =>
                    $data['notes']
                    ?? null,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */

            ActivityLog::create([

                'user_id' =>
                    $user->id,

                'action' =>
                    'Registou movimento de stock',

                'entity' =>
                    StockMovement::class,

                'entity_id' =>
                    $movement->id,

                'metadata' => [

                    'product_id' =>
                        $product->id,

                    'product' =>
                        $product->name,

                    'type' =>
                        $movement->type,

                    'quantity' =>
                        $quantity,

                    'balance_before' =>
                        $quantityBefore,

                    'balance_after' =>
                        $newBalance,

                    'reference' =>
                        $movement->reference,

                ],

            ]);

            /*
            |--------------------------------------------------------------------------
            | Resposta
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'message' =>
                    'Movimento de stock registado com sucesso.',

                'data' =>
                    $movement->load([
                        'product',
                        'user',
                    ]),

            ], 201);
        });
    }

    /**
     * Visualizar um movimento.
     */
    public function show(
        Request $request,
        StockMovement $stockMovement
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('movimentacoes.visualizar'),
            403,
            'Não possui permissão para visualizar movimentos de stock.'
        );

        return $stockMovement->load([
            'product',
            'user',
        ]);
    }

    /**
     * Movimentos de um produto específico.
     */
    public function stockByProduct(
        Request $request,
        Product $product
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('movimentacoes.visualizar'),
            403,
            'Não possui permissão para visualizar movimentos de stock.'
        );

        $query = StockMovement::query()
            ->where(
                'product_id',
                $product->id
            )
            ->with([
                'product',
                'user',
            ])
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | Filtro por tipo
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {

            $query->where(
                'type',
                $request->type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Paginação
        |--------------------------------------------------------------------------
        */

        $movements = $query->paginate(
            min(
                max(
                    $request->integer(
                        'per_page',
                        20
                    ),
                    1
                ),
                100
            )
        );

        return response()->json([

            'product' =>
                $product,

            'movements' =>
                $movements,

        ]);
    }

    /**
     * Inventário actual.
     */
    public function inventory(
        Request $request
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('armazem.inventario'),
            403,
            'Não possui permissão para consultar o inventário.'
        );

        $query = Product::query()
            ->with([
                'category',
                'supplier',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'sku',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Estado do stock
        |--------------------------------------------------------------------------
        */

        if (
            $request->boolean('low_stock')
        ) {

            $query->whereColumn(
                'quantity',
                '<=',
                'minimum_quantity'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Apenas produtos activos
        |--------------------------------------------------------------------------
        */

        if (
            $request->has('active')
        ) {

            $query->where(
                'active',
                $request->boolean('active')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ordenação
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'name',
            'sku',
            'quantity',
            'minimum_quantity',
            'created_at',
        ];

        $sortBy = $request->get(
            'sort_by',
            'name'
        );

        $sortDirection = strtolower(
            $request->get(
                'sort_direction',
                'asc'
            )
        );

        if (!in_array(
            $sortBy,
            $allowedSorts,
            true
        )) {
            $sortBy = 'name';
        }

        if (!in_array(
            $sortDirection,
            ['asc', 'desc'],
            true
        )) {
            $sortDirection = 'asc';
        }

        $query->orderBy(
            $sortBy,
            $sortDirection
        );

        return $query->paginate(
            min(
                max(
                    $request->integer(
                        'per_page',
                        20
                    ),
                    1
                ),
                100
            )
        );
    }

    /**
     * Produtos com stock crítico.
     */
    public function lowStock(
        Request $request
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('armazem.visualizar_stock'),
            403,
            'Não possui permissão para visualizar o stock.'
        );

        $query = Product::query()
            ->with([
                'category',
                'supplier',
            ])
            ->whereColumn(
                'quantity',
                '<=',
                'minimum_quantity'
            )
            ->orderBy(
                'quantity',
                'asc'
            );

        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'sku',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        return $query->paginate(
            min(
                max(
                    $request->integer(
                        'per_page',
                        20
                    ),
                    1
                ),
                100
            )
        );
    }

    /**
     * Movimentos de stock são históricos.
     *
     * Não podem ser alterados.
     */
    public function update(
        Request $request,
        StockMovement $stockMovement
    ) {
        return response()->json([

            'message' =>
                'Movimentos de stock não podem ser alterados. '
                . 'Registe um novo movimento para corrigir o stock.',

        ], 405);
    }

    /**
     * Movimentos de stock são históricos.
     *
     * Não podem ser eliminados.
     */
    public function destroy(
        Request $request,
        StockMovement $stockMovement
    ) {
        return response()->json([

            'message' =>
                'Movimentos de stock não podem ser apagados.',

        ], 405);
    }
}
