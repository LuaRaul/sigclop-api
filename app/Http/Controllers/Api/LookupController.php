<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;

class LookupController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'roles' => ['administrador', 'director', 'logistica', 'armazem', 'solicitante', 'auditor'],
            'request_statuses' => ['pendente', 'em_analise', 'aprovado', 'rejeitado', 'em_distribuicao', 'concluido', 'cancelado'],
            'priorities' => ['baixa', 'normal', 'alta', 'urgente'],
            'distribution_statuses' => ['em_preparacao', 'em_transporte', 'entregue', 'cancelada'],
            'departments' => Department::orderBy('name')->get(),
            'document_categories' => DocumentCategory::orderBy('name')->get(),
            'product_categories' => ProductCategory::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'approval_users' => User::whereIn('role', ['administrador', 'director', 'logistica'])->orderBy('name')->get(),
        ]);
    }
}
