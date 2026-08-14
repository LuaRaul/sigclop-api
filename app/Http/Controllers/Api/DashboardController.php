<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Document;
use App\Models\InternalRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard principal do SIGCLOP.
     *
     * O conteúdo é determinado por:
     *
     * 1. Role do utilizador
     * 2. Permissions atribuídas
     * 3. Departamento do utilizador
     *
     * Director Geral
     *      -> visão nacional
     *
     * Director
     *      -> visão do seu departamento
     *
     * Funcionário
     *      -> visão dos seus próprios pedidos
     *
     * Funcionário Armazém
     *      -> visão operacional do armazém/distribuição
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Identificação do perfil
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('director_geral')) {
            $scope = 'nacional';
        } elseif ($user->hasRole('director')) {
            $scope = 'departamento';
        } elseif ($user->hasRole('funcionario')) {
            $scope = 'pessoal';
        } elseif ($user->hasRole('funcionario_armazem')) {
            $scope = 'armazem';
        } else {
            abort(
                403,
                'O utilizador não possui um perfil válido para o dashboard.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Base de pedidos
        |--------------------------------------------------------------------------
        */

        $requestsQuery = InternalRequest::query();

        /*
        |--------------------------------------------------------------------------
        | Director Geral
        |--------------------------------------------------------------------------
        */

        if ($scope === 'nacional') {
            // Sem filtro.
        }

        /*
        |--------------------------------------------------------------------------
        | Director
        |--------------------------------------------------------------------------
        */

        elseif ($scope === 'departamento') {
            $requestsQuery->where(
                'department_id',
                $user->department_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Funcionário
        |--------------------------------------------------------------------------
        */

        elseif ($scope === 'pessoal') {
            $requestsQuery->where(
                'requested_by',
                $user->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Funcionário Armazém
        |--------------------------------------------------------------------------
        */

        elseif ($scope === 'armazem') {
            // O armazém necessita de visão operacional dos pedidos.
        }

        /*
        |--------------------------------------------------------------------------
        | CARDS
        |--------------------------------------------------------------------------
        */

        $cards = [];

        /*
        |--------------------------------------------------------------------------
        | Documentos
        |--------------------------------------------------------------------------
        */

        if ($user->can('documentos.visualizar')) {
            $documentsQuery = Document::query();

            /*
            | Director vê documentos do seu departamento.
            |
            | Director Geral vê todos.
            |
            | Funcionário vê documentos do seu departamento quando
            | existe departamento associado.
            */

            if (
                in_array($scope, ['departamento', 'pessoal'], true)
                && $user->department_id
            ) {
                $documentsQuery->where(
                    'department_id',
                    $user->department_id
                );
            }

            $cards['documents'] = $documentsQuery->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Pedidos pendentes
        |--------------------------------------------------------------------------
        */

        if ($user->can('pedidos.visualizar')) {
            $cards['requests_pending'] = (clone $requestsQuery)
                ->where('status', 'pendente')
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Produtos / Stock
        |--------------------------------------------------------------------------
        */

        if (
            $user->can('armazem.visualizar_stock')
            || $user->can('movimentacoes.visualizar')
        ) {
            $cards['products'] = Product::count();

            $cards['low_stock'] = Product::whereColumn(
                'quantity',
                '<=',
                'minimum_quantity'
            )->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Distribuições
        |--------------------------------------------------------------------------
        */

        if ($user->can('distribuicao.visualizar')) {
            $distributionsQuery = Distribution::query();

            /*
            | Director vê apenas distribuições do seu departamento.
            |
            | Director Geral e Funcionário Armazém possuem visão operacional
            | global.
            */

            if (
                $scope === 'departamento'
                && $user->department_id
            ) {
                $distributionsQuery->where(
                    'department_id',
                    $user->department_id
                );
            }

            $cards['distributions'] = $distributionsQuery->count();
        }

        /*
        |--------------------------------------------------------------------------
        | PEDIDOS RECENTES
        |--------------------------------------------------------------------------
        */

        $recentRequests = [];

        if ($user->can('pedidos.visualizar')) {
            $recentRequests = (clone $requestsQuery)
                ->with([
                    'department',
                    'requester',
                    'approver',
                    'items.product',
                ])
                ->latest()
                ->take(6)
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK CRÍTICO
        |--------------------------------------------------------------------------
        */

        $lowStockProducts = [];

        if (
            $user->can('armazem.visualizar_stock')
            || $user->can('movimentacoes.visualizar')
        ) {
            $lowStockProducts = Product::query()
                ->whereColumn(
                    'quantity',
                    '<=',
                    'minimum_quantity'
                )
                ->orderBy('quantity')
                ->take(6)
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | RESPOSTA
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'scope' => $scope,

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->getRoleNames()->first(),
                'department_id' => $user->department_id,
            ],

            'cards' => $cards,

            'recent_requests' => $recentRequests,

            'low_stock_products' => $lowStockProducts,
        ]);
    }
}
