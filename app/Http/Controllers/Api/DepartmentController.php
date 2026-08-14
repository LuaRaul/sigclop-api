<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Lista departamentos
     */
    public function index(Request $request)
    {
        $query = Department::query()
            ->withCount([
                'users',
                'documents',
                'internalRequests',
                'distributions'
            ]);

        // Pesquisa
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        // Activo/Inactivo
        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Ordenação
        $query->orderBy(
            $request->get('sort_by', 'name'),
            $request->get('sort_direction', 'asc')
        );

        return $query->paginate(
            $request->integer('per_page', 20)
        );
    }

    /**
     * Criar departamento
     */
    public function store(Request $request)
    {
        $this->authorize('departments.create');

        $data = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:30',
                'unique:departments,code',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'active' => [
                'boolean',
            ],

        ]);

        $department = Department::create($data);

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Criou departamento',

            'entity' => Department::class,

            'entity_id' => $department->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'department' => $department->name,

            ],

        ]);

        return response()->json([

            'message' => 'Departamento criado com sucesso.',

            'data' => $department,

        ], 201);
    }

    /**
     * Visualizar
     */
    public function show(Department $department)
    {
        $this->authorize('departments.view');

        return $department->load([

            'users',

            'documents',

            'internalRequests',

            'distributions',

        ]);
    }

    /**
     * Actualizar
     */
    public function update(Request $request, Department $department)
    {
        $this->authorize('departments.update');

        $data = $request->validate([

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'code' => [

                'sometimes',

                'required',

                'string',

                'max:30',

                Rule::unique('departments')
                    ->ignore($department->id),

            ],

            'description' => [

                'nullable',

                'string',

            ],

            'active' => [

                'boolean',

            ],

        ]);

        $old = $department->toArray();

        $department->update($data);

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Actualizou departamento',

            'entity' => Department::class,

            'entity_id' => $department->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'old' => $old,

                'new' => $department->fresh(),

            ],

        ]);

        return response()->json([

            'message' => 'Departamento actualizado com sucesso.',

            'data' => $department,

        ]);
    }

    /**
     * Remover
     */
    public function destroy(Department $department)
    {
        $this->authorize('departments.delete');

        if ($department->users()->exists()) {

            return response()->json([

                'message' =>
                'Não é possível eliminar um departamento com utilizadores associados.'

            ], 422);
        }

        if ($department->internalRequests()->exists()) {

            return response()->json([

                'message' =>
                'Não é possível eliminar um departamento que possui pedidos.'

            ], 422);
        }

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Eliminou departamento',

            'entity' => Department::class,

            'entity_id' => $department->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'department' => $department->name,

            ],

        ]);

        $department->delete();

        return response()->json([

            'message' => 'Departamento eliminado com sucesso.'

        ]);
    }
}
