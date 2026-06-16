<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Distribution;
use App\Models\Document;
use App\Models\InternalRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return response()->json([
            'totals' => [
                'pedidos' => InternalRequest::count(),
                'aprovados' => InternalRequest::where('status', 'aprovado')->count(),
                'pendentes' => InternalRequest::where('status', 'pendente')->count(),
                'produtos' => Product::count(),
                'stock_critico' => Product::whereColumn('quantity', '<=', 'minimum_quantity')->count(),
                'distribuicoes' => Distribution::count(),
                'documentos' => Document::count(),
                'sectores' => Department::count(),
                'utilizadores' => User::count(),
            ],
            'requests_by_status' => InternalRequest::select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->get(),
            'stock_movements_by_type' => StockMovement::select('type', DB::raw('COUNT(*) as total'), DB::raw('SUM(quantity) as quantity'))->groupBy('type')->get(),
            'low_stock_products' => Product::whereColumn('quantity', '<=', 'minimum_quantity')->orderBy('quantity')->take(10)->get(),
            'recent_distributions' => Distribution::with('items.product')->latest()->take(10)->get(),
        ]);
    }
}
