<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas não são válidas.'],
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Este utilizador encontra-se inactivo.'],
            ]);
        }

        $this->ensureSystemAdminAccess($user);

        ActivityLog::create([
            'user_id'   => $user->id,
            'action'    => 'Iniciou sessão',
            'entity'    => 'User',
            'entity_id' => $user->id,
            'metadata'  => [
                'ip' => $request->ip(),
            ],
        ]);

        $token = $user->createToken('sigclop')->plainTextToken;

        $user->load('department');

        return response()->json([
            'token' => $token,

            'user' => $user,

            'roles' => $user->getRoleNames()->values(),

            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->values(),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $this->ensureSystemAdminAccess($user);
        $user->load('department');

        return response()->json([

            'user' => $user,

            'roles' => $user->getRoleNames()->values(),

            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->values(),
        ]);
    }

    private function ensureSystemAdminAccess(User $user): void
    {
        if (strtolower((string) $user->email) !== 'admin@sigclop.local') {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $permissions = Permission::where('guard_name', 'web')->get();
        $role->syncPermissions($permissions);
        $user->syncRoles([$role]);
        $user->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function logout(Request $request)
    {
        ActivityLog::create([
            'user_id'   => $request->user()->id,
            'action'    => 'Terminou sessão',
            'entity'    => 'User',
            'entity_id' => $request->user()->id,
            'metadata'  => [
                'ip' => $request->ip(),
            ],
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sessão terminada com sucesso.',
        ]);
    }
}
