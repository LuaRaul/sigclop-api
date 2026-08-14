<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DistributionController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\InternalRequestController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ApprovalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| SIGCLOP API
|
| Autenticação:
|   Laravel Sanctum
|
| Autorização:
|   Spatie Laravel Permission
|
*/

/*
|--------------------------------------------------------------------------
| AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| ROTAS AUTENTICADAS
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Sessão
    |--------------------------------------------------------------------------
    */

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', DashboardController::class)
        ->middleware(
            'permission:dashboard.nacional|dashboard.regional|dashboard.local'
        );


    /*
    |--------------------------------------------------------------------------
    | LOOKUPS
    |--------------------------------------------------------------------------
    */

    Route::get('/lookups', LookupController::class)
        ->middleware(
            'permission:dashboard.nacional|dashboard.regional|dashboard.local'
        );


    /*
    |--------------------------------------------------------------------------
    | RELATÓRIOS
    |--------------------------------------------------------------------------
    */

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:relatorios.visualizar');


    /*
    |--------------------------------------------------------------------------
    | UTILIZADORES
    |--------------------------------------------------------------------------
    */

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:utilizadores.visualizar');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:utilizadores.criar');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('permission:utilizadores.visualizar');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:utilizadores.editar');

    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:utilizadores.editar');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:utilizadores.eliminar');


    /*
    |--------------------------------------------------------------------------
    | DEPARTAMENTOS
    |--------------------------------------------------------------------------
    */

    Route::get('/departments', [DepartmentController::class, 'index'])
        ->middleware('permission:departamentos.visualizar');

    Route::post('/departments', [DepartmentController::class, 'store'])
        ->middleware('permission:departamentos.criar');

    Route::get('/departments/{department}', [DepartmentController::class, 'show'])
        ->middleware('permission:departamentos.visualizar');

    Route::put('/departments/{department}', [DepartmentController::class, 'update'])
        ->middleware('permission:departamentos.editar');

    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])
        ->middleware('permission:departamentos.editar');

    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('permission:departamentos.eliminar');


    /*
    |--------------------------------------------------------------------------
    | FORNECEDORES
    |--------------------------------------------------------------------------
    */

    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:fornecedores.visualizar');

    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:fornecedores.criar');

    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])
        ->middleware('permission:fornecedores.visualizar');

    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:fornecedores.editar');

    Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:fornecedores.editar');

    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:fornecedores.eliminar');


    /*
    |--------------------------------------------------------------------------
    | PRODUTOS
    |--------------------------------------------------------------------------
    */

    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('permission:produtos.visualizar');

    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('permission:produtos.criar');

    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:produtos.visualizar');

    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:produtos.editar');

    Route::patch('/products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:produtos.editar');

    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:produtos.eliminar');


    /*
    |--------------------------------------------------------------------------
    | DOCUMENTOS
    |--------------------------------------------------------------------------
    */

    Route::get('/documents', [DocumentController::class, 'index'])
        ->middleware('permission:documentos.visualizar');

    Route::post('/documents', [DocumentController::class, 'store'])
        ->middleware('permission:documentos.criar');

    Route::get('/documents/{document}', [DocumentController::class, 'show'])
        ->middleware('permission:documentos.visualizar');

    Route::put('/documents/{document}', [DocumentController::class, 'update'])
        ->middleware('permission:documentos.editar');

    Route::patch('/documents/{document}', [DocumentController::class, 'update'])
        ->middleware('permission:documentos.editar');

    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
        ->middleware('permission:documentos.eliminar');


    /*
|--------------------------------------------------------------------------
| APROVAÇÕES
|--------------------------------------------------------------------------
*/

    Route::get(
        '/approvals',
        [ApprovalController::class, 'index']
    )->middleware(
        'permission:pedidos.visualizar'
    );

    Route::get(
        '/approvals/pending',
        [ApprovalController::class, 'pending']
    )->middleware(
        'permission:pedidos.aprovar'
    );

    Route::get(
        '/approvals/{approval}',
        [ApprovalController::class, 'show']
    )->middleware(
        'permission:pedidos.visualizar'
    );

    Route::post(
        '/approvals/internal-request/{internalRequest}/approve',
        [ApprovalController::class, 'approve']
    )->middleware(
        'permission:pedidos.aprovar'
    );

    Route::post(
        '/approvals/internal-request/{internalRequest}/reject',
        [ApprovalController::class, 'reject']
    )->middleware(
        'permission:pedidos.rejeitar'
    );

    Route::post(
        '/approvals/internal-request/{internalRequest}/forward',
        [ApprovalController::class, 'forward']
    )->middleware(
        'permission:pedidos.reencaminhar'
    );

    /*
    |--------------------------------------------------------------------------
    | PEDIDOS INTERNOS
    |--------------------------------------------------------------------------
    */

    Route::get('/internal-requests', [InternalRequestController::class, 'index'])
        ->middleware('permission:pedidos.visualizar');

    Route::post('/internal-requests', [InternalRequestController::class, 'store'])
        ->middleware('permission:pedidos.criar');

    Route::get('/internal-requests/{internalRequest}', [InternalRequestController::class, 'show'])
        ->middleware('permission:pedidos.visualizar');

    Route::put('/internal-requests/{internalRequest}', [InternalRequestController::class, 'update'])
        ->middleware('permission:pedidos.editar');

    Route::patch('/internal-requests/{internalRequest}', [InternalRequestController::class, 'update'])
        ->middleware('permission:pedidos.editar');

    Route::delete('/internal-requests/{internalRequest}', [InternalRequestController::class, 'destroy'])
        ->middleware('permission:pedidos.eliminar');

    /*
    |--------------------------------------------------------------------------
    | MOVIMENTOS DE STOCK
    |--------------------------------------------------------------------------
    |
    | Movimentos são históricos.
    | Não permitimos update/delete através da API.
    |
    */

    Route::get('/stock-movements', [StockMovementController::class, 'index'])
        ->middleware('permission:movimentacoes.visualizar');

    Route::post('/stock-movements', [StockMovementController::class, 'store'])
        ->middleware('permission:movimentacoes.registar');

    Route::get('/stock-movements/{stockMovement}', [StockMovementController::class, 'show'])
        ->middleware('permission:movimentacoes.visualizar');

    Route::get(
        '/stock-movements/product/{product}',
        [StockMovementController::class, 'stockByProduct']
    )->middleware('permission:movimentacoes.visualizar');

    Route::get(
        '/stock-movements/inventory',
        [StockMovementController::class, 'inventory']
    )->middleware('permission:armazem.inventario');

    Route::get(
        '/stock-movements/low-stock',
        [StockMovementController::class, 'lowStock']
    )->middleware('permission:armazem.visualizar_stock');


    /*
|--------------------------------------------------------------------------
| DISTRIBUIÇÕES
|--------------------------------------------------------------------------
*/

    Route::get(
        '/distributions',
        [DistributionController::class, 'index']
    )->middleware(
        'permission:distribuicao.visualizar'
    );

    Route::post(
        '/distributions',
        [DistributionController::class, 'store']
    )->middleware(
        'permission:distribuicao.criar'
    );

    Route::get(
        '/distributions/{distribution}',
        [DistributionController::class, 'show']
    )->middleware(
        'permission:distribuicao.visualizar'
    );

    Route::put(
        '/distributions/{distribution}',
        [DistributionController::class, 'update']
    )->middleware(
        'permission:distribuicao.editar'
    );

    Route::patch(
        '/distributions/{distribution}',
        [DistributionController::class, 'update']
    )->middleware(
        'permission:distribuicao.editar'
    );

    /*
|--------------------------------------------------------------------------
| Fluxo operacional
|--------------------------------------------------------------------------
*/

    Route::post(
        '/distributions/{distribution}/approve',
        [DistributionController::class, 'approve']
    )->middleware(
        'permission:distribuicao.aprovar'
    );

    Route::post(
        '/distributions/{distribution}/dispatch',
        [DistributionController::class, 'dispatch']
    )->middleware(
        'permission:distribuicao.entregar'
    );

    Route::post(
        '/distributions/{distribution}/deliver',
        [DistributionController::class, 'deliver']
    )->middleware(
        'permission:distribuicao.entregar'
    );

    Route::post(
        '/distributions/{distribution}/receive',
        [DistributionController::class, 'receive']
    )->middleware(
        'permission:distribuicao.entregar'
    );

    Route::post(
        '/distributions/{distribution}/confirm',
        [DistributionController::class, 'confirm']
    )->middleware(
        'permission:distribuicao.entregar'
    );

    Route::post(
        '/distributions/{distribution}/cancel',
        [DistributionController::class, 'cancel']
    )->middleware(
        'permission:distribuicao.cancelar'
    );

    Route::delete(
        '/distributions/{distribution}',
        [DistributionController::class, 'destroy']
    )->middleware(
        'permission:distribuicao.eliminar'
    );

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOGS
    |--------------------------------------------------------------------------
    */

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:auditoria.visualizar');

    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->middleware('permission:auditoria.visualizar');


    /*
    |--------------------------------------------------------------------------
    | CONFIGURAÇÕES
    |--------------------------------------------------------------------------
    */

    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:configuracoes.visualizar');

    Route::post('/settings', [SettingController::class, 'store'])
        ->middleware('permission:configuracoes.editar');

    Route::get('/settings/{setting}', [SettingController::class, 'show'])
        ->middleware('permission:configuracoes.visualizar');

    Route::put('/settings/{setting}', [SettingController::class, 'update'])
        ->middleware('permission:configuracoes.editar');

    Route::patch('/settings/{setting}', [SettingController::class, 'update'])
        ->middleware('permission:configuracoes.editar');

    Route::delete('/settings/{setting}', [SettingController::class, 'destroy'])
        ->middleware('permission:configuracoes.editar');
});
