<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    /**
     * Lista documentos respeitando o escopo do utilizador.
     *
     * Director Geral
     *     -> todos os documentos
     *
     * Director
     *     -> documentos do seu departamento
     *
     * Funcionário
     *     -> documentos do seu departamento
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Document::with([
            'category',
            'department',
            'user',
            'approvedBy',
        ]);

        /*
        |--------------------------------------------------------------------------
        | ESCOPOS RBAC
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('director_geral')) {

            // Acesso nacional.
        } elseif (
            $user->hasRole('director')
            || $user->hasRole('funcionario')
        ) {

            if (!$user->department_id) {

                $query->whereRaw('1 = 0');

            } else {

                $query->where(
                    'department_id',
                    $user->department_id
                );
            }

        } else {

            abort(
                403,
                'Não possui permissão para consultar documentos.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PESQUISA
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'title',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'reference_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'summary',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORIA
        |--------------------------------------------------------------------------
        */

        if ($request->filled('document_category_id')) {

            $query->where(
                'document_category_id',
                $request->document_category_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DEPARTAMENTO
        |--------------------------------------------------------------------------
        |
        | Apenas Director Geral pode escolher livremente o departamento.
        | Para os restantes perfis, o filtro continua limitado ao próprio
        | departamento.
        |
        */

        if (
            $user->hasRole('director_geral')
            && $request->filled('department_id')
        ) {

            $query->where(
                'department_id',
                $request->department_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ESTADO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ORDENAÇÃO
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'title',
            'reference_number',
            'document_date',
            'status',
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

        if (!in_array($sortBy, $allowedSorts, true)) {
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
        | PAGINAÇÃO
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
     * Criar documento.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->can('documentos.criar'),
            403,
            'Não possui permissão para criar documentos.'
        );

        $data = $request->validate([

            'document_category_id' => [
                'required',
                'exists:document_categories,id',
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'reference_number' => [
                'required',
                'string',
                'max:100',
                'unique:documents,reference_number',
            ],

            'document_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'rascunho',
                    'pendente',
                    'aprovado',
                    'publicado',
                    'arquivado',
                    'cancelado',
                ]),
            ],

            'file_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'file_path' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mime_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'file_size' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'version' => [
                'nullable',
                'string',
                'max:50',
            ],

            'expires_at' => [
                'nullable',
                'date',
            ],

            'summary' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Departamento
        |--------------------------------------------------------------------------
        |
        | O Director Geral pode definir o departamento.
        |
        | Caso não seja informado, fica associado ao departamento do
        | utilizador quando disponível.
        |
        */

        if (
            empty($data['department_id'])
            && $user->department_id
        ) {
            $data['department_id'] =
                $user->department_id;
        }

        /*
        |--------------------------------------------------------------------------
        | Utilizador responsável
        |--------------------------------------------------------------------------
        */

        $data['user_id'] = $user->id;

        /*
        |--------------------------------------------------------------------------
        | Estado padrão
        |--------------------------------------------------------------------------
        */

        $data['status'] ??= 'rascunho';

        $document = Document::create($data);

        /*
        |--------------------------------------------------------------------------
        | AUDITORIA
        |--------------------------------------------------------------------------
        */

        ActivityLog::create([

            'user_id' => $user->id,

            'action' => 'Criou documento',

            'entity' => Document::class,

            'entity_id' => $document->id,

            'metadata' => [

                'title' =>
                    $document->title,

                'reference' =>
                    $document->reference_number,

                'department_id' =>
                    $document->department_id,

                'status' =>
                    $document->status,
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | RESPOSTA
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'message' =>
                'Documento criado com sucesso.',

            'data' => $document->load([
                'category',
                'department',
                'user',
                'approvedBy',
            ]),

        ], 201);
    }

    /**
     * Visualizar documento.
     */
    public function show(
        Request $request,
        Document $document
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('documentos.visualizar'),
            403,
            'Não possui permissão para visualizar documentos.'
        );

        /*
        |--------------------------------------------------------------------------
        | Verificação de escopo
        |--------------------------------------------------------------------------
        */

        $this->ensureCanAccessDocument(
            $user,
            $document
        );

        return $document->load([

            'category',

            'department',

            'user',

            'approvedBy',

        ]);
    }

    /**
     * Actualizar documento.
     */
    public function update(
        Request $request,
        Document $document
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('documentos.editar'),
            403,
            'Não possui permissão para editar documentos.'
        );

        $this->ensureCanAccessDocument(
            $user,
            $document
        );

        $data = $request->validate([

            'document_category_id' => [
                'sometimes',
                'required',
                'exists:document_categories,id',
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'reference_number' => [

                'sometimes',

                'required',

                'string',

                'max:100',

                Rule::unique('documents')
                    ->ignore($document->id),

            ],

            'document_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'rascunho',
                    'pendente',
                    'aprovado',
                    'publicado',
                    'arquivado',
                    'cancelado',
                ]),
            ],

            'file_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'file_path' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mime_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'file_size' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'version' => [
                'nullable',
                'string',
                'max:50',
            ],

            'expires_at' => [
                'nullable',
                'date',
            ],

            'summary' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Departamento
        |--------------------------------------------------------------------------
        |
        | Apenas Director Geral pode mover um documento para outro
        | departamento.
        |
        */

        if (
            !$user->hasRole('director_geral')
            && array_key_exists(
                'department_id',
                $data
            )
            && $data['department_id'] !=
                $document->department_id
        ) {

            abort(
                403,
                'Não pode alterar o departamento deste documento.'
            );
        }

        $old = $document->toArray();

        $document->update($data);

        /*
        |--------------------------------------------------------------------------
        | AUDITORIA
        |--------------------------------------------------------------------------
        */

        ActivityLog::create([

            'user_id' => $user->id,

            'action' =>
                'Actualizou documento',

            'entity' =>
                Document::class,

            'entity_id' =>
                $document->id,

            'metadata' => [

                'old' => $old,

                'new' =>
                    $document->fresh()->toArray(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | RESPOSTA
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'message' =>
                'Documento actualizado com sucesso.',

            'data' =>
                $document
                    ->fresh()
                    ->load([
                        'category',
                        'department',
                        'user',
                        'approvedBy',
                    ]),

        ]);
    }

    /**
     * Remover documento.
     */
    public function destroy(
        Request $request,
        Document $document
    ) {
        $user = $request->user();

        abort_if(
            !$user->can('documentos.eliminar'),
            403,
            'Não possui permissão para eliminar documentos.'
        );

        $this->ensureCanAccessDocument(
            $user,
            $document
        );

        $documentData = [

            'title' =>
                $document->title,

            'reference_number' =>
                $document->reference_number,

            'department_id' =>
                $document->department_id,
        ];

        /*
        |--------------------------------------------------------------------------
        | AUDITORIA ANTES DA REMOÇÃO
        |--------------------------------------------------------------------------
        */

        ActivityLog::create([

            'user_id' =>
                $user->id,

            'action' =>
                'Removeu documento',

            'entity' =>
                Document::class,

            'entity_id' =>
                $document->id,

            'metadata' =>
                $documentData,
        ]);

        $document->delete();

        return response()->json([

            'message' =>
                'Documento removido com sucesso.',

        ]);
    }

    /**
     * Garante que o utilizador pode aceder ao documento.
     */
    private function ensureCanAccessDocument(
        $user,
        Document $document
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Director Geral
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('director_geral')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Director / Funcionário
        |--------------------------------------------------------------------------
        */

        if (
            $user->hasAnyRole([
                'director',
                'funcionario',
            ])
        ) {

            if (
                !$user->department_id
                || $document->department_id !=
                    $user->department_id
            ) {

                abort(
                    403,
                    'Não possui acesso a documentos deste departamento.'
                );
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Outros perfis
        |--------------------------------------------------------------------------
        */

        abort(
            403,
            'Não possui acesso a este documento.'
        );
    }
}
