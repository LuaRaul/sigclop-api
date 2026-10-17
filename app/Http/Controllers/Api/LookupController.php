<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;
use Spatie\Permission\Models\Role;

class LookupController extends Controller
{
    public function __invoke()
    {
        return response()->json([

            /*
            |--------------------------------------------------------------------------
            | Perfis
            |--------------------------------------------------------------------------
            */

            'roles' => Role::orderBy('name')
                ->get([
                    'id',
                    'name'
                ]),

            /*
            |--------------------------------------------------------------------------
            | Estados dos Pedidos
            |--------------------------------------------------------------------------
            */

            'request_statuses' => [

                [
                    'value' => 'pendente',
                    'label' => 'Pendente'
                ],

                [
                    'value' => 'em_analise',
                    'label' => 'Em Análise'
                ],

                [
                    'value' => 'aprovado',
                    'label' => 'Aprovado'
                ],

                [
                    'value' => 'rejeitado',
                    'label' => 'Rejeitado'
                ],

                [
                    'value' => 'em_distribuicao',
                    'label' => 'Em Distribuição'
                ],

                [
                    'value' => 'concluido',
                    'label' => 'Concluído'
                ],

                [
                    'value' => 'cancelado',
                    'label' => 'Cancelado'
                ]

            ],

            /*
            |--------------------------------------------------------------------------
            | Prioridades
            |--------------------------------------------------------------------------
            */

            'priorities' => [

                [
                    'value' => 'baixa',
                    'label' => 'Baixa'
                ],

                [
                    'value' => 'normal',
                    'label' => 'Normal'
                ],

                [
                    'value' => 'alta',
                    'label' => 'Alta'
                ],

                [
                    'value' => 'urgente',
                    'label' => 'Urgente'
                ]

            ],

            /*
            |--------------------------------------------------------------------------
            | Distribuição
            |--------------------------------------------------------------------------
            */

            'distribution_statuses' => [

                [
                    'value' => 'em_preparacao',
                    'label' => 'Em Preparação'
                ],

                [
                    'value' => 'em_transporte',
                    'label' => 'Em Transporte'
                ],

                [
                    'value' => 'entregue',
                    'label' => 'Entregue'
                ],

                [
                    'value' => 'cancelada',
                    'label' => 'Cancelada'
                ]

            ],

            /*
            |--------------------------------------------------------------------------
            | Departamentos
            |--------------------------------------------------------------------------
            */

            'departments' => Department::orderBy('name')->get(),

            'request_types' => [
                ['value' => 'vestuario', 'label' => 'Vestuário'],
                ['value' => 'higiene', 'label' => 'Artigos de higiene'],
                ['value' => 'meios_oficina', 'label' => 'Meios de oficina'],
            ],

            /*
            |--------------------------------------------------------------------------
            | Categorias Documentais
            |--------------------------------------------------------------------------
            */

            'document_categories' => DocumentCategory::orderBy('name')->get(),

            /*
            |--------------------------------------------------------------------------
            | Categorias de Utensílios
            |--------------------------------------------------------------------------
            */

            'product_categories' => ProductCategory::orderBy('name')->get(),

            /*
            |--------------------------------------------------------------------------
            | Utensílios
            |--------------------------------------------------------------------------
            */

            'products' => Product::where('active', true)
                ->orderBy('name')
                ->get(),

            /*
            |--------------------------------------------------------------------------
            | Fornecedores
            |--------------------------------------------------------------------------
            */

            'suppliers' => Supplier::where('active', true)
                ->orderBy('name')
                ->get(),

            /*
            |--------------------------------------------------------------------------
            | Utilizadores
            |--------------------------------------------------------------------------
            */

            'users' => User::with('roles')
                ->where('active', true)
                ->orderBy('name')
                ->get(),

            /*
            |--------------------------------------------------------------------------
            | Aprovadores
            |--------------------------------------------------------------------------
            */

            'approval_users' => User::role([
                'director_geral',
                'director'
            ])
                ->where('active', true)
                ->orderBy('name')
                ->get(),

            /*
            |--------------------------------------------------------------------------
            | Funcionários Armazém
            |--------------------------------------------------------------------------
            */

            'warehouse_users' => User::role('funcionario_armazem')
                ->where('active', true)
                ->orderBy('name')
                ->get(),

            /*
            |--------------------------------------------------------------------------
            | Funcionários
            |--------------------------------------------------------------------------
            */

            'employees' => User::role('funcionario')
                ->where('active', true)
                ->orderBy('name')
                ->get(),

        ]);
    }
}
