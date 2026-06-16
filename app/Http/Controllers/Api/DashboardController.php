<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Document;
use App\Models\InternalRequest;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'cards' => [
                'documents' => Document::count(),
                'requests_pending' => InternalRequest::where('status', 'pendente')->count(),
                'products' => Product::count(),
                'low_stock' => Product::whereColumn('quantity', '<=', 'minimum_quantity')->count(),
                'distributions' => Distribution::count(),
            ],
            'recent_requests' => InternalRequest::latest()->take(6)->with('items.product')->get(),
            'low_stock_products' => Product::whereColumn('quantity', '<=', 'minimum_quantity')->take(6)->get(),
        ]);
    }
}
