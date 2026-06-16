<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return Setting::orderBy('group')->orderBy('key')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120', 'unique:settings,key'],
            'value' => ['nullable', 'string'],
            'group' => ['required', 'string', 'max:80'],
            'type' => ['required', 'string', 'max:40'],
        ]);

        $setting = Setting::create($data);
        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'Criou configuracao',
            'entity' => 'Setting',
            'entity_id' => $setting->id,
            'metadata' => $setting->toArray(),
        ]);

        return $setting;
    }

    public function show(Setting $setting)
    {
        return $setting;
    }

    public function update(Request $request, Setting $setting)
    {
        $setting->update($request->validate([
            'key' => ['sometimes', 'required', 'string', 'max:120', 'unique:settings,key,' . $setting->id],
            'value' => ['nullable', 'string'],
            'group' => ['sometimes', 'required', 'string', 'max:80'],
            'type' => ['sometimes', 'required', 'string', 'max:40'],
        ]));

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'Actualizou configuracao',
            'entity' => 'Setting',
            'entity_id' => $setting->id,
            'metadata' => $setting->toArray(),
        ]);

        return $setting;
    }

    public function destroy(Request $request, Setting $setting)
    {
        $setting->delete();

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'Removeu configuracao',
            'entity' => 'Setting',
            'entity_id' => $setting->id,
        ]);

        return response()->noContent();
    }
}
