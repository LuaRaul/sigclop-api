<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Lista configurações.
     */
    public function index(Request $request)
    {
        $query = Setting::query();

        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Grupo
        |--------------------------------------------------------------------------
        */

        if ($request->filled('group')) {

            $query->where('group', $request->group);
        }

        /*
        |--------------------------------------------------------------------------
        | Tipo
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {

            $query->where('type', $request->type);
        }

        /*
        |--------------------------------------------------------------------------
        | Público
        |--------------------------------------------------------------------------
        */

        if ($request->filled('is_public')) {

            $query->where(
                'is_public',
                $request->boolean('is_public')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Editável
        |--------------------------------------------------------------------------
        */

        if ($request->filled('editable')) {

            $query->where(
                'editable',
                $request->boolean('editable')
            );
        }

        $query->orderBy('group')
            ->orderBy('key');

        return $query->paginate(
            $request->integer('per_page', 20)
        );
    }

    /**
     * Criar configuração.
     */
    public function store(Request $request)
    {
        $this->authorize('settings.update');

        $data = $request->validate([

            'key' => [
                'required',
                'string',
                'max:100',
                'unique:settings,key',
            ],

            'value' => [
                'nullable',
            ],

            'group' => [
                'required',
                'string',
                'max:50',
            ],

            'type' => [
                'required',
                'in:text,number,boolean,date,json,email,url,file',
            ],

            'label' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_public' => [
                'boolean',
            ],

            'editable' => [
                'boolean',
            ],

        ]);

        $data['updated_by'] = auth()->id();

        $setting = Setting::create($data);

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Criou configuração',

            'entity' => Setting::class,

            'entity_id' => $setting->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => $setting->toArray(),

        ]);

        return response()->json([

            'message' => 'Configuração criada com sucesso.',

            'data' => $setting,

        ], 201);
    }

    /**
     * Visualizar configuração.
     */
    public function show(Setting $setting)
    {
        $this->authorize('settings.view');

        return $setting;
    }

    /**
     * Actualizar configuração.
     */
    public function update(Request $request, Setting $setting)
    {
        $this->authorize('settings.update');

        $data = $request->validate([

            'key' => [

                'sometimes',

                'required',

                'string',

                'max:100',

                Rule::unique('settings')
                    ->ignore($setting->id),

            ],

            'value' => [
                'nullable',
            ],

            'group' => [
                'sometimes',
                'required',
                'string',
                'max:50',
            ],

            'type' => [
                'sometimes',
                'required',
                'in:text,number,boolean,date,json,email,url,file',
            ],
            'label' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_public' => [
                'boolean',
            ],

            'editable' => [
                'boolean',
            ],

        ]);

        $old = $setting->toArray();

        $data['updated_by'] = auth()->id();

        $setting->update($data);

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Actualizou configuração',

            'entity' => Setting::class,

            'entity_id' => $setting->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'old' => $old,

                'new' => $setting->fresh(),

            ],

        ]);

        return response()->json([

            'message' => 'Configuração actualizada com sucesso.',

            'data' => $setting,

        ]);
    }

    /**
     * Remover configuração.
     */
    public function destroy(Setting $setting)
    {
        $this->authorize('settings.update');

        if (! $setting->editable) {

            return response()->json([

                'message' => 'Esta configuração não pode ser removida.'

            ], 422);
        }

        ActivityLog::create([

            'user_id' => auth()->id(),

            'action' => 'Removeu configuração',

            'entity' => Setting::class,

            'entity_id' => $setting->id,

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

            'url' => request()->fullUrl(),

            'http_method' => request()->method(),

            'metadata' => [

                'key' => $setting->key,

            ],

        ]);

        $setting->delete();

        return response()->json([

            'message' => 'Configuração removida com sucesso.'

        ]);
    }
}
