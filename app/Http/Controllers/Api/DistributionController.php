<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Distribution;
use App\Models\InternalRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.visualizar'),
            403,
            'Não possui permissão para visualizar distribuições.'
        );

        $query = Distribution::query()
            ->with([
                'department',
                'request.requester',
                'request.approver',
                'items.product.category',
                'items.requestItem',
                'deliveredBy',
                'receivedBy',
                'confirmedBy',
            ]);

        $this->applyVisibility(
            $query,
            $user
        );

        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('department_id')) {
            $query->where(
                'department_id',
                $request->integer('department_id')
            );
        }

        if ($request->filled('internal_request_id')) {
            $query->where(
                'internal_request_id',
                $request->integer('internal_request_id')
            );
        }

        if ($request->filled('confirmed')) {
            $query->where(
                'confirmed',
                filter_var(
                    $request->confirmed,
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'distribution_number',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'receipt_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'vehicle',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'driver',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'request',
                        function ($q) use ($search) {
                            $q->where(
                                'request_number',
                                'like',
                                "%{$search}%"
                            );
                        }
                    )
                    ->orWhereHas(
                        'items.product',
                        function ($q) use ($search) {
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
                        }
                    );
            });
        }

        $allowedSorts = [
            'id',
            'distribution_number',
            'status',
            'prepared_at',
            'dispatched_at',
            'delivered_at',
            'received_at',
            'created_at',
        ];

        $sortBy = $request->get(
            'sort_by',
            'created_at'
        );

        $direction = strtolower(
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
            $direction,
            ['asc', 'desc'],
            true
        )) {
            $direction = 'desc';
        }

        $query->orderBy(
            $sortBy,
            $direction
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

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    |
    | A distribuição é criada somente a partir de um pedido aprovado.
    | A saída de stock acontece UMA vez, neste momento.
    |
    */

    public function store(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.criar'),
            403,
            'Não possui permissão para criar distribuições.'
        );

        /*
        |--------------------------------------------------------------------------
        | Para criar distribuição é necessário poder efectuar saída.
        |--------------------------------------------------------------------------
        */

        abort_if(
            !$user->can('armazem.saida_stock'),
            403,
            'Não possui permissão para efectuar saída de stock.'
        );

        $data = $request->validate([
            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'internal_request_id' => [
                'required',
                'exists:internal_requests,id',
            ],

            'vehicle' => [
                'nullable',
                'string',
                'max:255',
            ],

            'driver' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.request_item_id' => [
                'required',
                'integer',
                'exists:request_items,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.notes' => [
                'nullable',
                'string',
            ],
        ]);

        return DB::transaction(
            function () use (
                $data,
                $user
            ) {

                $internalRequest =
                    InternalRequest::query()
                        ->with('items')
                        ->lockForUpdate()
                        ->findOrFail(
                            $data['internal_request_id']
                        );

                if (
                    $internalRequest->status !==
                    'aprovado'
                ) {
                    throw ValidationException::withMessages([
                        'internal_request_id' => [
                            'Apenas pedidos aprovados podem originar uma distribuição.'
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Evitar duas distribuições simultâneas para o mesmo pedido.
                |--------------------------------------------------------------------------
                */

                $existingDistribution =
                    Distribution::query()
                        ->where(
                            'internal_request_id',
                            $internalRequest->id
                        )
                        ->whereNotIn(
                            'status',
                            ['cancelada']
                        )
                        ->exists();

                if ($existingDistribution) {
                    throw ValidationException::withMessages([
                        'internal_request_id' => [
                            'Este pedido já possui uma distribuição activa.'
                        ],
                    ]);
                }

                $departmentId =
                    $data['department_id']
                    ?? $internalRequest->department_id
                    ?? $user->department_id;

                if (
                    $user->hasRole('director')
                    &&
                    (int) $departmentId !==
                    (int) $user->department_id
                ) {
                    abort(
                        403,
                        'Não pode criar uma distribuição para outro departamento.'
                    );
                }

                $requestItems =
                    $internalRequest
                        ->items
                        ->keyBy('id');

                $distributionItems = [];

                foreach (
                    $data['items']
                    as $item
                ) {

                    $requestItem =
                        $requestItems->get(
                            (int) $item['request_item_id']
                        );

                    if (!$requestItem) {
                        throw ValidationException::withMessages([
                            'items' => [
                                'O item indicado não pertence ao pedido.'
                            ],
                        ]);
                    }

                    $quantity =
                        (int) $item['quantity'];

                    $approvedQuantity =
                        (int) $requestItem
                            ->quantity_approved;

                    if (
                        $approvedQuantity <= 0
                    ) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "O item {$requestItem->description} "
                                . "não possui quantidade aprovada."
                            ],
                        ]);
                    }

                    if (
                        $quantity >
                        $approvedQuantity
                    ) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "A quantidade distribuída para "
                                . "{$requestItem->description} "
                                . "não pode exceder "
                                . "{$approvedQuantity}."
                            ],
                        ]);
                    }

                    if (!$requestItem->product_id) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "O item {$requestItem->description} "
                                . "não possui produto associado."
                            ],
                        ]);
                    }

                    $distributionItems[] = [
                        'request_item' => $requestItem,
                        'quantity' => $quantity,
                        'notes' =>
                            $item['notes']
                            ?? null,
                    ];
                }

                $distributionNumber =
                    $this->generateDistributionNumber();

                $distribution =
                    Distribution::create([
                        'distribution_number' =>
                            $distributionNumber,

                        'internal_request_id' =>
                            $internalRequest->id,

                        'department_id' =>
                            $departmentId,

                        'delivered_by' =>
                            $user->id,

                        'status' =>
                            'em_preparacao',

                        'vehicle' =>
                            $data['vehicle']
                            ?? null,

                        'driver' =>
                            $data['driver']
                            ?? null,

                        'prepared_at' =>
                            now(),

                        'notes' =>
                            $data['notes']
                            ?? null,
                    ]);

                foreach (
                    $distributionItems
                    as $distributionItem
                ) {

                    $requestItem =
                        $distributionItem[
                            'request_item'
                        ];

                    $quantity =
                        $distributionItem[
                            'quantity'
                        ];

                    $product =
                        Product::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $requestItem->product_id
                            );

                    $balanceBefore =
                        (int) $product->quantity;

                    if (
                        $balanceBefore <
                        $quantity
                    ) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "Stock insuficiente para "
                                . "{$product->name}. "
                                . "Disponível: {$balanceBefore}; "
                                . "solicitado: {$quantity}."
                            ],
                        ]);
                    }

                    $balanceAfter =
                        $balanceBefore -
                        $quantity;

                    $product->update([
                        'quantity' =>
                            $balanceAfter,
                    ]);

                    $distribution->items()->create([
                        'product_id' =>
                            $product->id,

                        'request_item_id' =>
                            $requestItem->id,

                        'quantity_requested' =>
                            (int) $requestItem
                                ->quantity_requested,

                        'quantity_distributed' =>
                            $quantity,

                        'quantity_received' =>
                            0,

                        'quantity_returned' =>
                            0,

                        'status' =>
                            'pendente',

                        'notes' =>
                            $distributionItem[
                                'notes'
                            ],
                    ]);

                    StockMovement::create([
                        'product_id' =>
                            $product->id,

                        'user_id' =>
                            $user->id,

                        'distribution_id' =>
                            $distribution->id,

                        'internal_request_id' =>
                            $internalRequest->id,

                        'type' =>
                            'distribuicao',

                        'quantity' =>
                            $quantity,

                        'balance_before' =>
                            $balanceBefore,

                        'balance_after' =>
                            $balanceAfter,

                        'reference' =>
                            $distributionNumber,

                        'notes' =>
                            'Saída de stock por distribuição logística.',
                    ]);
                }

                $internalRequest->update([
                    'status' =>
                        'em_distribuicao',
                ]);

                $this->log(
                    $user->id,
                    'Criou distribuição',
                    $distribution,
                    [
                        'distribution_number' =>
                            $distributionNumber,

                        'internal_request_id' =>
                            $internalRequest->id,
                    ]
                );

                return response()->json([
                    'message' =>
                        'Distribuição criada com sucesso.',

                    'data' =>
                        $distribution
                            ->fresh()
                            ->load([
                                'department',
                                'request.requester',
                                'request.approver',
                                'items.product',
                                'items.requestItem',
                            ]),
                ], 201);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.visualizar'),
            403,
            'Não possui permissão para visualizar distribuições.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        return $distribution->load([
            'department',
            'request.requester',
            'request.approver',
            'request.items.product',
            'request.approvals.approver',
            'items.product.category',
            'items.requestItem',
            'deliveredBy',
            'receivedBy',
            'confirmedBy',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    |
    | Update fica reservado para dados administrativos.
    | As transições de estado usam métodos específicos.
    |
    */

    public function update(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.editar'),
            403,
            'Não possui permissão para editar distribuições.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        if ($distribution->isFinalized()) {
            abort(
                409,
                'Uma distribuição finalizada não pode ser editada.'
            );
        }

        $data = $request->validate([
            'vehicle' => [
                'nullable',
                'string',
                'max:255',
            ],

            'driver' => [
                'nullable',
                'string',
                'max:255',
            ],

            'receipt_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $before =
            $distribution->only([
                'vehicle',
                'driver',
                'receipt_number',
                'notes',
            ]);

        $distribution->update(
            $data
        );

        $this->log(
            $user->id,
            'Actualizou dados da distribuição',
            $distribution,
            [
                'before' => $before,
                'after' =>
                    $distribution
                        ->fresh()
                        ->only([
                            'vehicle',
                            'driver',
                            'receipt_number',
                            'notes',
                        ]),
            ]
        );

        return $distribution
            ->fresh()
            ->load([
                'department',
                'request.requester',
                'request.approver',
                'items.product',
                'items.requestItem',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APROVAR
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.aprovar'),
            403,
            'Não possui permissão para aprovar distribuições.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        if (
            $distribution->status !==
            'em_preparacao'
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'Apenas distribuições em preparação podem ser aprovadas.'
                ],
            ]);
        }

        $distribution->update([
            'status' =>
                'aprovada',
        ]);

        $this->log(
            $user->id,
            'Aprovou distribuição',
            $distribution,
            [
                'status' => 'aprovada',
            ]
        );

        return $distribution
            ->fresh()
            ->load([
                'department',
                'request',
                'items.product',
                'items.requestItem',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DESPACHAR
    |--------------------------------------------------------------------------
    */

    public function dispatch(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.entregar'),
            403,
            'Não possui permissão para despachar distribuições.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        $data = $request->validate([
            'vehicle' => [
                'nullable',
                'string',
                'max:255',
            ],

            'driver' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        if (
            !in_array(
                $distribution->status,
                [
                    'aprovada',
                    'em_preparacao',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'A distribuição não pode ser despachada no estado actual.'
                ],
            ]);
        }

        $distribution->update([
            'status' =>
                'em_transporte',

            'vehicle' =>
                $data['vehicle']
                ?? $distribution->vehicle,

            'driver' =>
                $data['driver']
                ?? $distribution->driver,

            'dispatched_at' =>
                now(),
        ]);

        $distribution
            ->items()
            ->where(
                'status',
                'pendente'
            )
            ->update([
                'status' =>
                    'em_transporte',
            ]);

        $this->log(
            $user->id,
            'Despachou distribuição',
            $distribution,
            [
                'vehicle' =>
                    $distribution->vehicle,

                'driver' =>
                    $distribution->driver,
            ]
        );

        return $distribution
            ->fresh()
            ->load([
                'department',
                'request',
                'items.product',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ENTREGAR
    |--------------------------------------------------------------------------
    */

    public function deliver(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.entregar'),
            403,
            'Não possui permissão para registar entregas.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        $data = $request->validate([
            'receipt_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        if (
            $distribution->status !==
            'em_transporte'
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'A distribuição precisa estar em transporte para ser entregue.'
                ],
            ]);
        }

        $distribution->update([
            'status' =>
                'entregue',

            'receipt_number' =>
                $data['receipt_number']
                ?? $distribution->receipt_number,

            'delivered_at' =>
                now(),

            'notes' =>
                $data['notes']
                ?? $distribution->notes,
        ]);

        $distribution
            ->items()
            ->whereIn(
                'status',
                [
                    'pendente',
                    'em_transporte',
                ]
            )
            ->update([
                'status' =>
                    'entregue',

                'delivered_at' =>
                    now(),
            ]);

        $this->log(
            $user->id,
            'Registou entrega de distribuição',
            $distribution,
            [
                'receipt_number' =>
                    $distribution->receipt_number,
            ]
        );

        return $distribution
            ->fresh()
            ->load([
                'department',
                'request',
                'items.product',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RECEBER
    |--------------------------------------------------------------------------
    */

    public function receive(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.entregar'),
            403,
            'Não possui permissão para registar recepção.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        $data = $request->validate([
            'received_by' => [
                'nullable',
                'exists:users,id',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        if (
            $distribution->status !==
            'entregue'
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'Apenas uma distribuição entregue pode ser recebida.'
                ],
            ]);
        }

        $receivedBy =
            $data['received_by']
            ?? $user->id;

        $distribution->update([
            'status' =>
                'recebida',

            'received_by' =>
                $receivedBy,

            'received_at' =>
                now(),

            'notes' =>
                $data['notes']
                ?? $distribution->notes,
        ]);

        $distribution
            ->items()
            ->where(
                'status',
                'entregue'
            )
            ->update([
                'status' =>
                    'recebido',

                'quantity_received' =>
                    DB::raw(
                        'quantity_distributed'
                    ),

                'received_at' =>
                    now(),
            ]);

        $this->log(
            $user->id,
            'Registou recepção de distribuição',
            $distribution,
            [
                'received_by' =>
                    $receivedBy,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Pedido
        |--------------------------------------------------------------------------
        |
        | Só marcamos o pedido como concluído quando todos os seus
        | itens aprovados foram recebidos através das distribuições.
        |
        */

        $this->tryCompleteRequest(
            $distribution
                ->fresh()
        );

        return $distribution
            ->fresh()
            ->load([
                'department',
                'request',
                'items.product',
                'receivedBy',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    public function confirm(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.entregar'),
            403,
            'Não possui permissão para confirmar distribuições.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        if (
            $distribution->status !==
            'recebida'
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'Apenas distribuições recebidas podem ser confirmadas.'
                ],
            ]);
        }

        if ($distribution->confirmed) {
            return $distribution
                ->fresh()
                ->load([
                    'department',
                    'request',
                    'items.product',
                    'confirmedBy',
                ]);
        }

        $distribution->update([
            'confirmed' =>
                true,

            'confirmed_by' =>
                $user->id,

            'confirmed_at' =>
                now(),
        ]);

        $this->log(
            $user->id,
            'Confirmou distribuição',
            $distribution,
            [
                'confirmed' => true,
            ]
        );

        return $distribution
            ->fresh()
            ->load([
                'department',
                'request',
                'items.product',
                'confirmedBy',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CANCELAR
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.cancelar'),
            403,
            'Não possui permissão para cancelar distribuições.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        $data = $request->validate([
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:5000',
            ],
        ]);

        if (
            in_array(
                $distribution->status,
                [
                    'recebida',
                    'cancelada',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'Esta distribuição não pode ser cancelada.'
                ],
            ]);
        }

        return DB::transaction(
            function () use (
                $distribution,
                $user,
                $data
            ) {

                /*
                |--------------------------------------------------------------------------
                | Se já houve saída de stock, cancelar NÃO altera stock
                |--------------------------------------------------------------------------
                |
                | O stock continua a reflectir a saída física registada.
                | Uma eventual devolução deverá ser uma operação separada
                | de stock com tipo "devolucao".
                |
                */

                $distribution->update([
                    'status' =>
                        'cancelada',

                    'notes' =>
                        trim(
                            ($distribution->notes
                                ? $distribution->notes . PHP_EOL
                                : '')
                            . 'Cancelamento: '
                            . $data['reason']
                        ),
                ]);

                $distribution
                    ->items()
                    ->whereNotIn(
                        'status',
                        ['recebido']
                    )
                    ->update([
                        'status' =>
                            'devolvido',
                    ]);

                $this->log(
                    $user->id,
                    'Cancelou distribuição',
                    $distribution,
                    [
                        'reason' =>
                            $data['reason'],
                    ]
                );

                return $distribution
                    ->fresh()
                    ->load([
                        'department',
                        'request',
                        'items.product',
                    ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Distribution $distribution
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('distribuicao.eliminar'),
            403,
            'Não possui permissão para eliminar distribuições.'
        );

        $this->authorizeVisibility(
            $user,
            $distribution
        );

        /*
        |--------------------------------------------------------------------------
        | Nunca apagar uma distribuição que já movimentou stock.
        |--------------------------------------------------------------------------
        */

        $hasMovements =
            StockMovement::query()
                ->where(
                    'distribution_id',
                    $distribution->id
                )
                ->exists();

        if ($hasMovements) {
            abort(
                409,
                'Esta distribuição possui movimentos de stock e não pode ser eliminada.'
            );
        }

        if (
            $distribution->status !==
            'em_preparacao'
        ) {
            abort(
                409,
                'Apenas distribuições em preparação podem ser eliminadas.'
            );
        }

        return DB::transaction(
            function () use (
                $distribution,
                $user
            ) {

                $this->log(
                    $user->id,
                    'Eliminou distribuição',
                    $distribution,
                    [
                        'distribution_number' =>
                            $distribution
                                ->distribution_number,
                    ]
                );

                $distribution->delete();

                return response()->noContent();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VISIBILIDADE
    |--------------------------------------------------------------------------
    */

    private function applyVisibility(
        $query,
        $user
    ): void {

        if (
            $user->hasRole(
                'director_geral'
            )
        ) {
            return;
        }

        if (
            $user->hasRole(
                'funcionario_armazem'
            )
        ) {
            return;
        }

        if (
            $user->hasRole(
                'director'
            )
        ) {

            $query->where(
                'department_id',
                $user->department_id
            );

            return;
        }

        abort(
            403,
            'Não possui acesso às distribuições.'
        );
    }

    private function authorizeVisibility(
        $user,
        Distribution $distribution
    ): void {

        if (
            $user->hasRole(
                'director_geral'
            )
        ) {
            return;
        }

        if (
            $user->hasRole(
                'funcionario_armazem'
            )
        ) {
            return;
        }

        if (
            $user->hasRole(
                'director'
            )
        ) {

            abort_if(
                (int) $distribution->department_id !==
                (int) $user->department_id,
                403,
                'Não possui acesso a esta distribuição.'
            );

            return;
        }

        abort(
            403,
            'Não possui acesso a esta distribuição.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONCLUIR PEDIDO
    |--------------------------------------------------------------------------
    */

    private function tryCompleteRequest(
        Distribution $distribution
    ): void {

        if (
            !$distribution->internal_request_id
        ) {
            return;
        }

        $request =
            InternalRequest::query()
                ->with([
                    'items',
                ])
                ->lockForUpdate()
                ->find(
                    $distribution
                        ->internal_request_id
                );

        if (!$request) {
            return;
        }

        $approvedItems =
            $request
                ->items
                ->filter(
                    fn ($item) =>
                        (int) $item->quantity_approved > 0
                );

        if ($approvedItems->isEmpty()) {
            return;
        }

        $distributedQuantities =
            Distribution::query()
                ->where(
                    'internal_request_id',
                    $request->id
                )
                ->where(
                    'status',
                    'recebida'
                )
                ->with('items')
                ->get()
                ->flatMap(
                    fn ($distribution) =>
                        $distribution->items
                )
                ->groupBy(
                    'request_item_id'
                )
                ->map(
                    fn ($items) =>
                        (int) $items->sum(
                            'quantity_received'
                        )
                );

        foreach (
            $approvedItems
            as $requestItem
        ) {

            $approved =
                (int) $requestItem
                    ->quantity_approved;

            $received =
                (int) (
                    $distributedQuantities
                        ->get(
                            $requestItem->id,
                            0
                        )
                );

            if (
                $received < $approved
            ) {
                return;
            }
        }

        $request->update([
            'status' =>
                'concluido',

            'completed_at' =>
                now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NÚMERO DA DISTRIBUIÇÃO
    |--------------------------------------------------------------------------
    */

    private function generateDistributionNumber(): string
    {
        $prefix =
            'DIST-' .
            now()->format('Ymd') .
            '-';

        $last =
            Distribution::query()
                ->where(
                    'distribution_number',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

        $sequence = 1;

        if ($last) {

            $lastNumber =
                str_replace(
                    $prefix,
                    '',
                    $last->distribution_number
                );

            $sequence =
                ((int) $lastNumber) + 1;
        }

        return $prefix .
            str_pad(
                $sequence,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    /*
    |--------------------------------------------------------------------------
    | AUDITORIA
    |--------------------------------------------------------------------------
    */

    private function log(
        ?int $userId,
        string $action,
        Distribution $distribution,
        array $metadata = []
    ): void {

        ActivityLog::create([
            'user_id' =>
                $userId,

            'action' =>
                $action,

            'entity' =>
                Distribution::class,

            'entity_id' =>
                $distribution->id,

            'metadata' =>
                array_merge(
                    [
                        'distribution_number' =>
                            $distribution
                                ->distribution_number,
                    ],
                    $metadata
                ),
        ]);
    }
}
