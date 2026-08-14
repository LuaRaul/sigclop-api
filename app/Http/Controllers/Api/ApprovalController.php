<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\InternalRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalController extends Controller
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
            !$user->can('pedidos.visualizar'),
            403,
            'Não possui permissão para visualizar aprovações.'
        );

        $query = Approval::query()
            ->with([
                'request.department',
                'request.requester',
                'request.approver',
                'request.items.product',
                'approver',
            ]);

        /*
        |--------------------------------------------------------------------------
        | VISIBILIDADE
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('director_geral')) {

            // Acesso global.

        } elseif ($user->hasRole('director')) {

            $query->whereHas(
                'request',
                function ($q) use ($user) {
                    $q->where(
                        'department_id',
                        $user->department_id
                    );
                }
            );

        } elseif ($user->hasRole('funcionario')) {

            $query->whereHas(
                'request',
                function ($q) use ($user) {
                    $q->where(
                        'requested_by',
                        $user->id
                    );
                }
            );

        } elseif ($user->hasRole('funcionario_armazem')) {

            $query->whereHas(
                'request',
                function ($q) {
                    $q->whereIn(
                        'status',
                        [
                            'aprovado',
                            'em_distribuicao',
                            'concluido',
                        ]
                    );
                }
            );

        } else {

            abort(
                403,
                'Não possui acesso às aprovações.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('decision')) {

            $query->where(
                'decision',
                $request->decision
            );
        }

        if ($request->filled('approval_level')) {

            $query->where(
                'approval_level',
                $request->integer(
                    'approval_level'
                )
            );
        }

        if ($request->filled('internal_request_id')) {

            $query->where(
                'internal_request_id',
                $request->integer(
                    'internal_request_id'
                )
            );
        }

        if ($request->filled('approved_by')) {

            $query->where(
                'approved_by',
                $request->integer(
                    'approved_by'
                )
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
                    'comment',
                    'like',
                    "%{$search}%"
                )
                    ->orWhereHas(
                        'request',
                        function ($requestQuery) use ($search) {

                            $requestQuery
                                ->where(
                                    'request_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'justification',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'approver',
                        function ($approver) use ($search) {

                            $approver->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        return $query
            ->latest('created_at')
            ->paginate(
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
    | PENDENTES
    |--------------------------------------------------------------------------
    */

    public function pending(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.aprovar'),
            403,
            'Não possui permissão para visualizar aprovações pendentes.'
        );

        $query = InternalRequest::query()
            ->with([
                'department',
                'requester',
                'approver',
                'items.product.category',
                'approvals.approver',
            ])
            ->whereIn(
                'status',
                [
                    'pendente',
                    'em_analise',
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Director Geral
        |--------------------------------------------------------------------------
        */

        if (
            $user->hasRole(
                'director_geral'
            )
        ) {

            // Global.

        } elseif (
            $user->hasRole('director')
        ) {

            $query->where(
                'department_id',
                $user->department_id
            );

            /*
            |--------------------------------------------------------------------------
            | Se existir responsável específico,
            | mostrar apenas pedidos atribuídos a ele.
            |--------------------------------------------------------------------------
            */

            $query->where(function ($q) use ($user) {

                $q->where(
                    'assigned_approver_id',
                    $user->id
                )
                    ->orWhereNull(
                        'assigned_approver_id'
                    );
            });

        } else {

            abort(
                403,
                'Apenas responsáveis pela aprovação podem consultar esta fila.'
            );
        }

        return $query
            ->orderByRaw(
                "FIELD(priority, 'urgente', 'alta', 'normal', 'baixa')"
            )
            ->orderBy(
                'created_at',
                'asc'
            )
            ->paginate(
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
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Approval $approval
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.visualizar'),
            403,
            'Não possui permissão para visualizar aprovações.'
        );

        $approval->load([
            'request.department',
            'request.requester',
            'request.approver',
            'request.items.product.category',
            'request.approvals.approver',
            'approver',
        ]);

        $this->authorizeVisibility(
            $user,
            $approval->request
        );

        return $approval;
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        InternalRequest $internalRequest
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.aprovar'),
            403,
            'Não possui permissão para aprovar pedidos.'
        );

        $data = $request->validate([
            'comment' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'items' => [
                'nullable',
                'array',
            ],

            'items.*.request_item_id' => [
                'required_with:items',
                'integer',
                'exists:request_items,id',
            ],

            'items.*.quantity_approved' => [
                'required_with:items',
                'integer',
                'min:0',
            ],
        ]);

        return $this->decide(
            $user,
            $internalRequest,
            'aprovado',
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        InternalRequest $internalRequest
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.rejeitar'),
            403,
            'Não possui permissão para rejeitar pedidos.'
        );

        $data = $request->validate([
            'comment' => [
                'required',
                'string',
                'min:3',
                'max:5000',
            ],
        ]);

        return $this->decide(
            $user,
            $internalRequest,
            'rejeitado',
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REENCAMINHAR
    |--------------------------------------------------------------------------
    */

    public function forward(
        Request $request,
        InternalRequest $internalRequest
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('pedidos.reencaminhar'),
            403,
            'Não possui permissão para reencaminhar pedidos.'
        );

        $data = $request->validate([
            'assigned_approver_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        return DB::transaction(
            function () use (
                $data,
                $user,
                $internalRequest
            ) {

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
                            'Este pedido não pode ser reencaminhado neste estado.'
                        ],
                    ]);
                }

                $newApprover =
                    User::find(
                        $data[
                            'assigned_approver_id'
                        ]
                    );

                /*
                |--------------------------------------------------------------------------
                | O novo responsável precisa estar activo
                |--------------------------------------------------------------------------
                */

                if (
                    !$newApprover->active
                ) {
                    throw ValidationException::withMessages([
                        'assigned_approver_id' => [
                            'O utilizador seleccionado está inactivo.'
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Director não pode encaminhar para outro departamento
                |--------------------------------------------------------------------------
                */

                if (
                    $user->hasRole('director')
                    &&
                    (int) $newApprover->department_id !==
                    (int) $user->department_id
                ) {
                    abort(
                        403,
                        'Não pode reencaminhar o pedido para outro departamento.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Actualizar pedido
                |--------------------------------------------------------------------------
                */

                $oldApprover =
                    $internalRequest
                        ->assigned_approver_id;

                $internalRequest->update([

                    'assigned_approver_id' =>
                        $newApprover->id,

                    'status' =>
                        'em_analise',

                ]);

                /*
                |--------------------------------------------------------------------------
                | Registo da decisão
                |--------------------------------------------------------------------------
                */

                $approval = Approval::create([

                    'internal_request_id' =>
                        $internalRequest->id,

                    'approved_by' =>
                        $user->id,

                    'approval_level' =>
                        1,

                    'decision' =>
                        'reencaminhado',

                    'comment' =>
                        $data['comment']
                        ?? null,

                    'decided_at' =>
                        now(),

                ]);

                ActivityLog::create([

                    'user_id' =>
                        $user->id,

                    'action' =>
                        'Reencaminhou pedido para aprovação',

                    'entity' =>
                        InternalRequest::class,

                    'entity_id' =>
                        $internalRequest->id,

                    'metadata' => [

                        'request_number' =>
                            $internalRequest
                                ->request_number,

                        'old_approver_id' =>
                            $oldApprover,

                        'new_approver_id' =>
                            $newApprover->id,

                        'approval_id' =>
                            $approval->id,

                    ],

                ]);

                return response()->json([
                    'message' =>
                        'Pedido reencaminhado com sucesso.',

                    'data' =>
                        $internalRequest
                            ->fresh()
                            ->load([
                                'department',
                                'requester',
                                'approver',
                                'items.product',
                                'approvals.approver',
                            ]),
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DECISÃO
    |--------------------------------------------------------------------------
    */

    private function decide(
        User $user,
        InternalRequest $internalRequest,
        string $decision,
        array $data
    ) {
        return DB::transaction(
            function () use (
                $user,
                $internalRequest,
                $decision,
                $data
            ) {

                $internalRequest =
                    InternalRequest::query()
                        ->with([
                            'items',
                            'approvals',
                        ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $internalRequest->id
                        );

                /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                */

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
                            'Este pedido já não está disponível para decisão.'
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Responsável
                |--------------------------------------------------------------------------
                */

                if (
                    !$user->hasRole(
                        'director_geral'
                    )
                    &&
                    $internalRequest
                        ->assigned_approver_id
                    &&
                    (int) $internalRequest
                        ->assigned_approver_id
                    !==
                    (int) $user->id
                ) {
                    abort(
                        403,
                        'Este pedido está atribuído a outro responsável.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Rejeição
                |--------------------------------------------------------------------------
                */

                if (
                    $decision === 'rejeitado'
                ) {

                    $approval =
                        Approval::create([

                            'internal_request_id' =>
                                $internalRequest->id,

                            'approved_by' =>
                                $user->id,

                            'approval_level' =>
                                1,

                            'decision' =>
                                'rejeitado',

                            'comment' =>
                                $data['comment'],

                            'decided_at' =>
                                now(),

                        ]);

                    $internalRequest->update([

                        'status' =>
                            'rejeitado',

                    ]);

                    ActivityLog::create([

                        'user_id' =>
                            $user->id,

                        'action' =>
                            'Rejeitou pedido interno',

                        'entity' =>
                            InternalRequest::class,

                        'entity_id' =>
                            $internalRequest->id,

                        'metadata' => [

                            'request_number' =>
                                $internalRequest
                                    ->request_number,

                            'approval_id' =>
                                $approval->id,

                            'comment' =>
                                $data['comment'],

                        ],

                    ]);

                    return response()->json([
                        'message' =>
                            'Pedido rejeitado com sucesso.',

                        'data' =>
                            $internalRequest
                                ->fresh()
                                ->load([
                                    'department',
                                    'requester',
                                    'approver',
                                    'items.product',
                                    'approvals.approver',
                                ]),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Aprovação
                |--------------------------------------------------------------------------
                */

                $submittedItems =
                    collect(
                        $data['items'] ?? []
                    )->keyBy(
                        'request_item_id'
                    );

                /*
                |--------------------------------------------------------------------------
                | Se não forem enviadas quantidades,
                | aprovar integralmente os itens.
                |--------------------------------------------------------------------------
                */

                foreach (
                    $internalRequest->items
                    as $item
                ) {

                    if (
                        $submittedItems
                            ->has($item->id)
                    ) {

                        $approvedQuantity =
                            (int)
                            $submittedItems
                                ->get($item->id)
                                [
                                    'quantity_approved'
                                ];

                    } else {

                        $approvedQuantity =
                            (int)
                            $item->quantity_requested;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Nunca aprovar acima do solicitado
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $approvedQuantity >
                        $item->quantity_requested
                    ) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "A quantidade aprovada para "
                                . "{$item->description} "
                                . "não pode exceder "
                                . "{$item->quantity_requested}."
                            ],
                        ]);
                    }

                    $item->quantity_approved =
                        $approvedQuantity;

                    $item->save();
                }

                /*
                |--------------------------------------------------------------------------
                | Verificar se existe pelo menos um item aprovado
                |--------------------------------------------------------------------------
                */

                $approvedItems =
                    $internalRequest
                        ->items()
                        ->where(
                            'quantity_approved',
                            '>',
                            0
                        )
                        ->count();

                if (
                    $approvedItems === 0
                ) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Pelo menos um item deve ser aprovado.'
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Registo
                |--------------------------------------------------------------------------
                */

                $approval =
                    Approval::create([

                        'internal_request_id' =>
                            $internalRequest->id,

                        'approved_by' =>
                            $user->id,

                        'approval_level' =>
                            1,

                        'decision' =>
                            'aprovado',

                        'comment' =>
                            $data['comment']
                            ?? null,

                        'decided_at' =>
                            now(),

                    ]);

                $internalRequest->update([

                    'status' =>
                        'aprovado',

                    'assigned_approver_id' =>
                        $user->id,

                    'approved_at' =>
                        now(),

                ]);

                ActivityLog::create([

                    'user_id' =>
                        $user->id,

                    'action' =>
                        'Aprovou pedido interno',

                    'entity' =>
                        InternalRequest::class,

                    'entity_id' =>
                        $internalRequest->id,

                    'metadata' => [

                        'request_number' =>
                            $internalRequest
                                ->request_number,

                        'approval_id' =>
                            $approval->id,

                        'approved_quantity' =>
                            $internalRequest
                                ->items()
                                ->sum(
                                    'quantity_approved'
                                ),

                    ],

                ]);

                return response()->json([
                    'message' =>
                        'Pedido aprovado com sucesso.',

                    'data' =>
                        $internalRequest
                            ->fresh()
                            ->load([
                                'department',
                                'requester',
                                'approver',
                                'items.product',
                                'approvals.approver',
                            ]),
                ]);
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
        ?InternalRequest $internalRequest
    ): void {

        if (!$internalRequest) {
            abort(
                404,
                'Pedido não encontrado.'
            );
        }

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
                (int) $internalRequest->department_id !==
                (int) $user->department_id,
                403,
                'Não possui acesso a esta aprovação.'
            );

            return;
        }

        if (
            $user->hasRole('funcionario')
        ) {

            abort_if(
                (int) $internalRequest->requested_by !==
                (int) $user->id,
                403,
                'Não possui acesso a esta aprovação.'
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
                'Esta aprovação ainda não está disponível para o armazém.'
            );

            return;
        }

        abort(403);
    }
}
