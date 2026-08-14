<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Lista fornecedores.
     */
    public function index(Request $request)
    {
        $query = Supplier::query()
            ->withCount('products');

        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nif', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if ($request->filled('active')) {

            $query->where(
                'active',
                $request->boolean('active')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ordenação
        |--------------------------------------------------------------------------
        */

        $query->orderBy(
            $request->get('sort_by', 'name'),
            $request->get('sort_direction', 'asc')
        );

        return $query->paginate(
            $request->integer('per_page', 20)
        );
    }

    /**
     * Criar fornecedor.
     */
    public function store(Request $request)
    {
        $this->authorize('suppliers.create');

        $data = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'nif' => [
                'nullable',
                'string',
                'max:30',
                'unique:suppliers,nif',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'province' => [
                'nullable',
                'string',
                'max:100',
            ],

            'municipality' => [
                'nullable',
                'string',
                'max:100',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'contract_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'contract_start_date' => [
                'nullable',
                'date',
            ],

            'contract_end_date' => [
                'nullable',
                'date',
                'after_or_equal:contract_start_date',
            ],

            'observations' => [
                'nullable',
                'string',
            ],

            'active' => [
                'boolean',
            ],

        ]);

        $supplier = Supplier::create($data);

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Criou fornecedor',

            'entity' => Supplier::class,

            'entity_id' => $supplier->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'supplier' => $supplier->name,

            ],

        ]);

        return response()->json([

            'message' => 'Fornecedor criado com sucesso.',

            'data' => $supplier,

        ], 201);
    }

    /**
     * Visualizar fornecedor.
     */
    public function show(Supplier $supplier)
    {
        $this->authorize('suppliers.view');

        return $supplier->load([

            'products.category',

        ]);
    }

    /**
     * Actualizar fornecedor.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('suppliers.update');

        $data = $request->validate([

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'nif' => [

                'nullable',

                'string',

                'max:30',

                Rule::unique('suppliers')
                    ->ignore($supplier->id),

            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'province' => [
                'nullable',
                'string',
                'max:100',
            ],

            'municipality' => [
                'nullable',
                'string',
                'max:100',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'contract_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'contract_start_date' => [
                'nullable',
                'date',
            ],

            'contract_end_date' => [
                'nullable',
                'date',
                'after_or_equal:contract_start_date',
            ],

            'observations' => [
                'nullable',
                'string',
            ],

            'active' => [
                'boolean',
            ],

        ]);

        $old = $supplier->toArray();

        $supplier->update($data);

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Actualizou fornecedor',

            'entity' => Supplier::class,

            'entity_id' => $supplier->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'old' => $old,

                'new' => $supplier->fresh(),

            ],

        ]);

        return response()->json([

            'message' => 'Fornecedor actualizado com sucesso.',

            'data' => $supplier,

        ]);
    }

    /**
     * Remover fornecedor.
     */
    public function destroy(Supplier $supplier)
    {
        $this->authorize('suppliers.delete');

        if ($supplier->products()->exists()) {

            return response()->json([

                'message' => 'Não é possível eliminar um fornecedor que possui produtos associados.'

            ], 422);
        }

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Eliminou fornecedor',

            'entity' => Supplier::class,

            'entity_id' => $supplier->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'supplier' => $supplier->name,

            ],

        ]);

        $supplier->delete();

        return response()->json([

            'message' => 'Fornecedor eliminado com sucesso.'

        ]);
    }
}
