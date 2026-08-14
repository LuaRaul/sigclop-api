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

class ProductController extends Controller
{
    /**
     * Lista os produtos.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category',
                'supplier',
                'latestMovement.user',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

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
                    )
                    ->orWhere(
                        'barcode',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Categoria
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {

            $query->where(
                'product_category_id',
                $request->category
            );
        }

        if ($request->filled('product_category_id')) {

            $query->where(
                'product_category_id',
                $request->product_category_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fornecedor
        |--------------------------------------------------------------------------
        */

        if ($request->filled('supplier_id')) {

            $query->where(
                'supplier_id',
                $request->supplier_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if ($request->has('active')) {

            $query->where(
                'active',
                $request->boolean('active')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stock baixo
        |--------------------------------------------------------------------------
        */

        if ($request->boolean('low_stock')) {

            $query->whereColumn(
                'quantity',
                '<=',
                'minimum_quantity'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sem stock
        |--------------------------------------------------------------------------
        */

        if ($request->boolean('out_of_stock')) {

            $query->where(
                'quantity',
                '<=',
                0
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stock disponível
        |--------------------------------------------------------------------------
        */

        if ($request->boolean('available')) {

            $query->where(
                'quantity',
                '>',
                0
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
            'maximum_quantity',
            'estimated_unit_cost',
            'active',
            'created_at',
            'updated_at',
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

        $perPage = min(
            max(
                $request->integer(
                    'per_page',
                    20
                ),
                1
            ),
            100
        );

        return $query->paginate($perPage);
    }

    /**
     * Regista um novo produto.
     *
     * Se quantity > 0, é criada automaticamente uma
     * movimentação de entrada inicial.
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            'product_category_id' => [
                'required',
                'integer',
                'exists:product_categories,id',
            ],

            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:50',
                'unique:products,sku',
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                'unique:products,barcode',
            ],

            'unit' => [
                'required',
                'string',
                'max:30',
            ],

            /*
            |--------------------------------------------------------------------------
            | STOCK
            |--------------------------------------------------------------------------
            |
            | A quantidade inicial é permitida apenas na criação.
            | Depois de criado, o stock será controlado pelo
            | StockMovementController.
            |
            */

            'quantity' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'minimum_quantity' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'maximum_quantity' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'estimated_unit_cost' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | LOCALIZAÇÃO
            |--------------------------------------------------------------------------
            */

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADO
            |--------------------------------------------------------------------------
            */

            'active' => [
                'sometimes',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | DESCRIÇÃO
            |--------------------------------------------------------------------------
            */

            'description' => [
                'nullable',
                'string',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Valores por defeito
        |--------------------------------------------------------------------------
        */

        $quantity = (int) (
            $data['quantity'] ?? 0
        );

        $minimumQuantity = (int) (
            $data['minimum_quantity'] ?? 0
        );

        $maximumQuantity =
            array_key_exists(
                'maximum_quantity',
                $data
            )
                ? $data['maximum_quantity']
                : null;

        /*
        |--------------------------------------------------------------------------
        | Validar mínimo / máximo
        |--------------------------------------------------------------------------
        */

        if (
            $maximumQuantity !== null
            && $maximumQuantity < $minimumQuantity
        ) {

            throw ValidationException::withMessages([

                'maximum_quantity' => [

                    'A quantidade máxima não pode ser inferior à quantidade mínima.',

                ],

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Criar produto + movimento inicial
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $data,
            $request,
            $quantity
        ) {

            $product = new Product();

            /*
            |--------------------------------------------------------------------------
            | Identificação
            |--------------------------------------------------------------------------
            */

            $product->product_category_id =
                $data['product_category_id'];

            $product->supplier_id =
                $data['supplier_id']
                ?? null;

            $product->name =
                $data['name'];

            $product->sku =
                $data['sku'];

            $product->barcode =
                $data['barcode']
                ?? null;

            $product->unit =
                $data['unit'];

            /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

            $product->quantity =
                $quantity;

            $product->minimum_quantity =
                $data['minimum_quantity']
                ?? 0;

            $product->maximum_quantity =
                $data['maximum_quantity']
                ?? null;

            /*
            |--------------------------------------------------------------------------
            | Custos
            |--------------------------------------------------------------------------
            */

            $product->estimated_unit_cost =
                $data['estimated_unit_cost']
                ?? 0;

            /*
            |--------------------------------------------------------------------------
            | Localização
            |--------------------------------------------------------------------------
            */

            $product->location =
                $data['location']
                ?? null;

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $product->active =
                $data['active']
                ?? true;

            /*
            |--------------------------------------------------------------------------
            | Descrição
            |--------------------------------------------------------------------------
            */

            $product->description =
                $data['description']
                ?? null;

            $product->save();

            /*
            |--------------------------------------------------------------------------
            | Movimento inicial
            |--------------------------------------------------------------------------
            */

            $initialMovement = null;

            if ($quantity > 0) {

                $initialMovement =
                    StockMovement::create([

                        'product_id' =>
                            $product->id,

                        'user_id' =>
                            $request->user()?->id,

                        'type' =>
                            'entrada',

                        'quantity' =>
                            $quantity,

                        'balance_before' =>
                            0,

                        'balance_after' =>
                            $quantity,

                        'reference' =>
                            'STOCK-INICIAL-' . $product->id,

                        'notes' =>
                            'Stock inicial registado na criação do produto.',

                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */

            ActivityLog::create([

                'user_id' =>
                    $request->user()?->id,

                'action' =>
                    'Registou utensílio',

                'entity' =>
                    Product::class,

                'entity_id' =>
                    $product->id,

                'metadata' => [

                    'product' =>
                        $product->toArray(),

                    'initial_quantity' =>
                        $quantity,

                    'initial_movement_id' =>
                        $initialMovement?->id,

                ],

            ]);

            return response()->json([

                'message' =>
                    'Utensílio registado com sucesso.',

                'data' =>
                    $product
                        ->fresh()
                        ->load([
                            'category',
                            'supplier',
                            'latestMovement.user',
                        ]),

            ], 201);
        });
    }

    /**
     * Visualiza um produto.
     */
    public function show(Product $product)
    {
        return $product->load([
            'category',
            'supplier',
            'latestMovement.user',
        ]);
    }

    /**
     * Actualiza os dados de um produto.
     *
     * IMPORTANTE:
     * quantity NÃO pode ser alterado aqui.
     *
     * Alterações de stock devem passar pelo
     * StockMovementController.
     */
    public function update(
        Request $request,
        Product $product
    ) {

        /*
        |--------------------------------------------------------------------------
        | Impedir alteração directa do stock
        |--------------------------------------------------------------------------
        */

        if ($request->has('quantity')) {

            throw ValidationException::withMessages([

                'quantity' => [

                    'A quantidade de stock não pode ser alterada directamente. '
                    . 'Utilize o módulo de movimentos de stock.',

                ],

            ]);
        }

        $data = $request->validate([

            'product_category_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:product_categories,id',
            ],

            'supplier_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'products',
                    'sku'
                )->ignore($product->id),
            ],

            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique(
                    'products',
                    'barcode'
                )->ignore($product->id),
            ],

            'unit' => [
                'sometimes',
                'required',
                'string',
                'max:30',
            ],

            'minimum_quantity' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'maximum_quantity' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'estimated_unit_cost' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'location' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'active' => [
                'sometimes',
                'boolean',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Validar mínimo / máximo
        |--------------------------------------------------------------------------
        */

        $minimumQuantity = array_key_exists(
            'minimum_quantity',
            $data
        )
            ? (int) $data['minimum_quantity']
            : (int) $product->minimum_quantity;

        $maximumQuantity = array_key_exists(
            'maximum_quantity',
            $data
        )
            ? $data['maximum_quantity']
            : $product->maximum_quantity;

        if (
            $maximumQuantity !== null
            && $maximumQuantity < $minimumQuantity
        ) {

            throw ValidationException::withMessages([

                'maximum_quantity' => [

                    'A quantidade máxima não pode ser inferior à quantidade mínima.',

                ],

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Actualização
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $product,
            $data,
            $request
        ) {

            $before = $product
                ->fresh()
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Actualizar campos
            |--------------------------------------------------------------------------
            |
            | Não usamos update($data) para garantir que campos
            | adicionados à tabela mas ainda não incluídos no
            | $fillable do Model sejam tratados correctamente.
            |
            */

            foreach ($data as $field => $value) {

                $product->{$field} = $value;
            }

            $product->save();

            $after = $product
                ->fresh()
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */

            ActivityLog::create([

                'user_id' =>
                    $request->user()?->id,

                'action' =>
                    'Actualizou utensílio',

                'entity' =>
                    Product::class,

                'entity_id' =>
                    $product->id,

                'metadata' => [

                    'before' =>
                        $before,

                    'after' =>
                        $after,

                    'changed_fields' =>
                        array_keys($data),

                ],

            ]);

            return $product
                ->fresh()
                ->load([
                    'category',
                    'supplier',
                    'latestMovement.user',
                ]);
        });
    }

    /**
     * Remove um produto.
     *
     * Produtos que possuem histórico de stock não podem
     * ser eliminados fisicamente.
     */
    public function destroy(
        Request $request,
        Product $product
    ) {

        /*
        |--------------------------------------------------------------------------
        | Verificar histórico de stock
        |--------------------------------------------------------------------------
        */

        if (
            $product
                ->stockMovements()
                ->exists()
        ) {

            return response()->json([

                'message' =>
                    'Este utensílio possui histórico de movimentações '
                    . 'de stock e não pode ser eliminado.',

                'suggestion' =>
                    'Desactive o utensílio em vez de o eliminar.',

            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar utilização em pedidos
        |--------------------------------------------------------------------------
        */

        if (
            $product
                ->requestItems()
                ->exists()
        ) {

            return response()->json([

                'message' =>
                    'Este utensílio já foi utilizado em pedidos internos '
                    . 'e não pode ser eliminado.',

                'suggestion' =>
                    'Desactive o utensílio em vez de o eliminar.',

            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Auditoria antes da eliminação
        |--------------------------------------------------------------------------
        */

        ActivityLog::create([

            'user_id' =>
                $request->user()?->id,

            'action' =>
                'Removeu utensílio',

            'entity' =>
                Product::class,

            'entity_id' =>
                $product->id,

            'metadata' => [

                'name' =>
                    $product->name,

                'sku' =>
                    $product->sku,

                'barcode' =>
                    $product->barcode,

            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Eliminar
        |--------------------------------------------------------------------------
        */

        $product->delete();

        return response()->noContent();
    }
}
