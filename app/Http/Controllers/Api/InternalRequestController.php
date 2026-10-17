<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInternalRequestRequest;
use App\Http\Requests\UpdateInternalRequestRequest;
use App\Models\ActivityLog;
use App\Models\InternalRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InternalRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.visualizar'),
            403,
            'Não possui permissão para visualizar pedidos internos.'
        );

        $query = InternalRequest::query()
            ->with([
                'department',
                'requester',
                'approver',
                'items.product.category',
                'approvals.approver',
            ]);

        /*
        |--------------------------------------------------------------------------
        | VISIBILIDADE
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('director_geral')) {
            // Global.
        } elseif ($user->hasRole('director')) {
            $query->where(
                'department_id',
                $user->department_id
            );
        } elseif ($user->hasRole('funcionario')) {
            $query->where(
                'requested_by',
                $user->id
            );
        } elseif ($user->hasRole('funcionario_armazem')) {
            $query->whereIn(
                'status',
                [
                    'aprovado',
                    'em_distribuicao',
                    'concluido',
                ]
            );
        } else {
            abort(
                403,
                'Não possui acesso aos pedidos internos.'
            );
        }

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

        if ($request->filled('priority')) {
            $query->where(
                'priority',
                $request->priority
            );
        }

        if (
            $request->filled('department_id')
            && $user->hasRole('director_geral')
        ) {
            $query->where(
                'department_id',
                $request->department_id
            );
        }

        if ($request->filled('requested_by')) {
            $query->where(
                'requested_by',
                $request->requested_by
            );
        }

        if ($request->filled('assigned_approver_id')) {
            $query->where(
                'assigned_approver_id',
                $request->assigned_approver_id
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
        | PESQUISA
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'request_number',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'justification',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'requester',
                        function ($q) use ($search) {
                            $q->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'department',
                        function ($q) use ($search) {
                            $q->where(
                                'name',
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

        /*
        |--------------------------------------------------------------------------
        | ORDENAÇÃO
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'request_number',
            'priority',
            'status',
            'needed_at',
            'submitted_at',
            'created_at',
            'updated_at',
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
    */

    public function store(
        StoreInternalRequestRequest $request
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.criar'),
            403,
            'Não possui permissão para criar pedidos internos.'
        );

        $data = $request->validated();

        return DB::transaction(
            function () use (
                $data,
                $user
            ) {

                /*
                |--------------------------------------------------------------------------
                | Departamento
                |--------------------------------------------------------------------------
                */

                $departmentId =
                    $data['department_id']
                    ?? $user->department_id;

                if (!$departmentId) {
                    throw ValidationException::withMessages([
                        'department_id' => [
                            'É necessário indicar o departamento.'
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Responsável
                |--------------------------------------------------------------------------
                */

                $approver = null;

                if (
                    !empty(
                        $data['assigned_approver_id']
                    )
                ) {

                    $approver = User::query()
                        ->whereKey(
                            $data['assigned_approver_id']
                        )
                        ->where('active', true)
                        ->first();

                    if (!$approver) {
                        throw ValidationException::withMessages([
                            'assigned_approver_id' => [
                                'Responsável pela aprovação não encontrado ou está inactivo.'
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Director do departamento
                |--------------------------------------------------------------------------
                */

                if (!$approver) {

                    $approver = User::role(
                        'director'
                    )
                        ->where(
                            'department_id',
                            $departmentId
                        )
                        ->where(
                            'active',
                            true
                        )
                        ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Director Geral
                |--------------------------------------------------------------------------
                */

                if (!$approver) {

                    $approver = User::role(
                        'director_geral'
                    )
                        ->where(
                            'active',
                            true
                        )
                        ->first();
                }

                if (!$approver) {
                    throw ValidationException::withMessages([
                        'assigned_approver_id' => [
                            'Não existe nenhum responsável disponível para aprovação.'
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Número
                |--------------------------------------------------------------------------
                */

                $requestNumber =
                    $this->generateRequestNumber();

                /*
                |--------------------------------------------------------------------------
                | Pedido
                |--------------------------------------------------------------------------
                */

                $internalRequest =
                    InternalRequest::create([

                        'request_number' =>
                            $requestNumber,

                        'request_type' =>
                            $data['request_type'],

                        'department_id' =>
                            $departmentId,

                        'requested_by' =>
                            $user->id,

                        'assigned_approver_id' =>
                            $approver->id,

                        'priority' =>
                            $data['priority']
                            ?? 'normal',

                        'status' =>
                            'pendente',

                        'justification' =>
                            $data['justification']
                            ?? null,

                        'needed_at' =>
                            $data['needed_at']
                            ?? null,

                        'submitted_at' =>
                            now(),

                        'notes' =>
                            $data['notes']
                            ?? null,

                    ]);

                /*
                |--------------------------------------------------------------------------
                | Itens
                |--------------------------------------------------------------------------
                */

                foreach (
                    $data['items']
                    as $item
                ) {

                    $productId =
                        $item['product_id']
                        ?? null;

                    $description =
                        $item['description']
                        ?? null;

                    /*
                    |--------------------------------------------------------------------------
                    | Se existe produto, usar nome como fallback
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $productId
                        && !$description
                    ) {

                        $product =
                            \App\Models\Product::find(
                                $productId
                            );

                        $description =
                            $product?->name;
                    }

                    $internalRequest
                        ->items()
                        ->create([

                            'product_id' =>
                                $productId,

                            'description' =>
                                $description,

                            'quantity_requested' =>
                                (int)
                                $item[
                                    'quantity_requested'
                                ],

                            'quantity_approved' =>
                                0,

                            'notes' =>
                                $item['notes']
                                ?? null,

                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Auditoria
                |--------------------------------------------------------------------------
                */

                ActivityLog::create([

                    'user_id' =>
                        $user->id,

                    'action' =>
                        'Criou pedido interno',

                    'entity' =>
                        InternalRequest::class,

                    'entity_id' =>
                        $internalRequest->id,

                    'metadata' => [

                        'request_number' =>
                            $requestNumber,

                        'department_id' =>
                            $departmentId,

                        'assigned_approver_id' =>
                            $approver->id,

                        'items_count' =>
                            count(
                                $data['items']
                            ),

                    ],

                ]);

                return response()->json(
                    $internalRequest
                        ->fresh()
                        ->load([
                            'department',
                            'requester',
                            'approver',
                            'items.product.category',
                            'approvals.approver',
                        ]),
                    201
                );
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
        InternalRequest $internalRequest
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.visualizar'),
            403,
            'Não possui permissão para visualizar pedidos internos.'
        );

        $this->authorizeVisibility(
            $user,
            $internalRequest
        );

        return $internalRequest->load([
            'department',
            'requester',
            'approver',
            'items.product.category',
            'approvals.approver',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateInternalRequestRequest $request,
        InternalRequest $internalRequest
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.editar'),
            403,
            'Não possui permissão para editar pedidos internos.'
        );

        $this->authorizeVisibility(
            $user,
            $internalRequest
        );

        if (
            !in_array(
                $internalRequest->status,
                [
                    'pendente',
                    'em_analise',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'Apenas pedidos pendentes ou em análise podem ser editados.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Funcionário só pode editar os próprios pedidos
        |--------------------------------------------------------------------------
        */

        if (
            $user->hasRole('funcionario')
            &&
            (int) $internalRequest->requested_by
            !==
            (int) $user->id
        ) {
            abort(
                403,
                'Não pode editar este pedido.'
            );
        }

        $data = $request->validated();

        return DB::transaction(
            function () use (
                $data,
                $user,
                $internalRequest
            ) {

                $before =
                    $internalRequest->toArray();

                /*
                |--------------------------------------------------------------------------
                | Não permitir alteração arbitrária do aprovador
                |--------------------------------------------------------------------------
                */

                if (
                    isset(
                        $data[
                            'assigned_approver_id'
                        ]
                    )
                ) {

                    $approver =
                        User::query()
                            ->whereKey(
                                $data[
                                    'assigned_approver_id'
                                ]
                            )
                            ->where(
                                'active',
                                true
                            )
                            ->first();

                    if (!$approver) {
                        throw ValidationException::withMessages([
                            'assigned_approver_id' => [
                                'Responsável pela aprovação inválido.'
                            ],
                        ]);
                    }

                    $data[
                        'assigned_approver_id'
                    ] = $approver->id;
                }

                $internalRequest->update(
                    $data
                );

                ActivityLog::create([

                    'user_id' =>
                        $user->id,

                    'action' =>
                        'Actualizou pedido interno',

                    'entity' =>
                        InternalRequest::class,

                    'entity_id' =>
                        $internalRequest->id,

                    'metadata' => [

                        'request_number' =>
                            $internalRequest
                                ->request_number,

                        'before' =>
                            $before,

                        'after' =>
                            $internalRequest
                                ->fresh()
                                ->toArray(),

                    ],

                ]);

                return $internalRequest
                    ->fresh()
                    ->load([
                        'department',
                        'requester',
                        'approver',
                        'items.product.category',
                        'approvals.approver',
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
        InternalRequest $internalRequest
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.eliminar'),
            403,
            'Não possui permissão para eliminar pedidos internos.'
        );

        $this->authorizeVisibility(
            $user,
            $internalRequest
        );

        if (
            !in_array(
                $internalRequest->status,
                [
                    'pendente',
                    'em_analise',
                    'rejeitado',
                    'cancelado',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    'Este pedido não pode ser eliminado no estado actual.'
                ],
            ]);
        }

        $requestNumber =
            $internalRequest->request_number;

        return DB::transaction(
            function () use (
                $internalRequest,
                $user,
                $requestNumber
            ) {

                ActivityLog::create([

                    'user_id' =>
                        $user->id,

                    'action' =>
                        'Eliminou pedido interno',

                    'entity' =>
                        InternalRequest::class,

                    'entity_id' =>
                        $internalRequest->id,

                    'metadata' => [

                        'request_number' =>
                            $requestNumber,

                    ],

                ]);

                $internalRequest->delete();

                return response()->noContent();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VISIBILIDADE
    |--------------------------------------------------------------------------
    */

    private function authorizeVisibility(
        User $user,
        InternalRequest $internalRequest
    ): void {

        if (
            $user->hasRole(
                'director_geral'
            )
        ) {
            return;
        }

        if (
            $user->hasRole('director')
        ) {

            abort_if(
                !$user->department_id
                ||
                (int) $internalRequest
                    ->department_id
                !==
                (int) $user
                    ->department_id,
                403,
                'Não possui acesso a este pedido.'
            );

            return;
        }

        if (
            $user->hasRole('funcionario')
        ) {

            abort_if(
                (int) $internalRequest
                    ->requested_by
                !==
                (int) $user->id,
                403,
                'Não possui acesso a este pedido.'
            );

            return;
        }

        if (
            $user->hasRole(
                'funcionario_armazem'
            )
        ) {

            abort_if(
                !in_array(
                    $internalRequest->status,
                    [
                        'aprovado',
                        'em_distribuicao',
                        'concluido',
                    ],
                    true
                ),
                403,
                'Este pedido ainda não está disponível para o armazém.'
            );

            return;
        }

        abort(
            403,
            'Não possui acesso a este pedido.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NUMERAÇÃO
    |--------------------------------------------------------------------------
    */

    private function generateRequestNumber(): string
    {
        do {

            $number =
                'REQ-' .
                now()->format('YmdHis') .
                '-' .
                random_int(
                    100,
                    999
                );

        } while (
            InternalRequest::query()
                ->where(
                    'request_number',
                    $number
                )
                ->exists()
        );

        return $number;
    }
}
