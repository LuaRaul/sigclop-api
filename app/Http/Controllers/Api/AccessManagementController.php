<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessManagementController extends Controller
{
    private const ADMIN_EMAIL = 'admin@sigclop.local';

    public function __construct()
    {
        // Gestão de permissões/perfis fica exclusivamente reservada ao administrador inicial.
        $this->middleware(function ($request, $next) {
            abort_unless(
                strtolower((string) $request->user()?->email) === self::ADMIN_EMAIL,
                403,
                'A gestão de perfis e permissões está reservada ao administrador do sistema.'
            );

            return $next($request);
        });
    }

    public function overview()
    {
        $this->ensureAdminHasAllPermissions();

        return response()->json([
            'roles' => Role::with('permissions:id,name,guard_name')
                ->orderBy('name')->get()
                ->map(fn (Role $role) => $this->serializeRole($role)),
            'permissions' => Permission::query()->orderBy('name')->get(),
            'users' => User::with(['department:id,name', 'roles:id,name,guard_name', 'permissions:id,name,guard_name'])
                ->orderBy('name')->get()
                ->map(fn (User $user) => $this->serializeUserAccess($user)),
        ]);
    }

    public function roles()
    {
        return Role::with('permissions:id,name,guard_name')
            ->orderBy('name')->get()
            ->map(fn (Role $role) => $this->serializeRole($role));
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            $role->syncPermissions($data['permissions'] ?? []);
            $this->ensureAdminHasAllPermissions();
            $this->log($request, 'Criou perfil', Role::class, $role->id, ['name' => $role->name]);
            return response()->json($this->serializeRole($role->load('permissions')), 201);
        });
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_unless($role->guard_name === 'web', 404);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        return DB::transaction(function () use ($data, $request, $role) {
            abort_if($role->name === 'administrador' && isset($data['name']) && $data['name'] !== 'administrador', 422, 'O perfil administrador não pode ser renomeado.');
            if (isset($data['name'])) $role->name = $data['name'];
            $role->save();
            if (array_key_exists('permissions', $data)) $role->syncPermissions($data['permissions']);
            $this->ensureAdminHasAllPermissions();
            $this->log($request, 'Actualizou perfil', Role::class, $role->id, ['name' => $role->name]);
            return $this->serializeRole($role->fresh()->load('permissions'));
        });
    }

    public function destroyRole(Request $request, Role $role)
    {
        abort_if($role->name === 'administrador', 422, 'O perfil administrador não pode ser eliminado.');
        $assignedUsersExist = DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $role->id)
            ->where('model_type', User::class)
            ->exists();

        abort_if($assignedUsersExist, 422, 'Não é possível eliminar um perfil associado a utilizadores. Reatribua primeiro os utilizadores.');
        $id = $role->id;
        $name = $role->name;
        $role->delete();
        $this->log($request, 'Eliminou perfil', Role::class, $id, ['name' => $name]);
        return response()->noContent();
    }

    public function permissions()
    {
        return Permission::query()->where('guard_name', 'web')->orderBy('name')->get();
    }

    public function storePermission(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $permission = Permission::create(['name' => $data['name'], 'guard_name' => 'web']);
        $this->ensureAdminHasAllPermissions();
        $this->log($request, 'Criou permissão', Permission::class, $permission->id, ['name' => $permission->name]);
        return response()->json($permission, 201);
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        abort_unless($permission->guard_name === 'web', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission->id)],
        ]);

        $oldName = $permission->name;
        $permission->name = $data['name'];
        $permission->save();
        $this->ensureAdminHasAllPermissions();
        $this->log($request, 'Actualizou permissão', Permission::class, $permission->id, ['old_name' => $oldName, 'name' => $permission->name]);
        return $permission->fresh();
    }

    public function destroyPermission(Request $request, Permission $permission)
    {
        abort_unless($permission->guard_name === 'web', 404);
        $id = $permission->id;
        $name = $permission->name;
        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->ensureAdminHasAllPermissions();
        $this->log($request, 'Eliminou permissão', Permission::class, $id, ['name' => $name]);
        return response()->noContent();
    }

    public function userAccess(User $user)
    {
        return $this->serializeUserAccess($user->load(['roles', 'permissions', 'department']));
    }

    public function updateUserAccess(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        if (strtolower($user->email) === self::ADMIN_EMAIL) {
            $this->ensureAdminHasAllPermissions();
            return response()->json([
                'message' => 'O administrador principal mantém obrigatoriamente todas as permissões do sistema.',
                'user' => $this->serializeUserAccess($user->fresh()->load(['roles', 'permissions', 'department'])),
            ]);
        }

        return DB::transaction(function () use ($data, $request, $user) {
            $user->syncRoles($data['roles']);
            $user->syncPermissions($data['permissions']);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->log($request, 'Actualizou acessos do utilizador', User::class, $user->id, ['roles' => $data['roles'], 'permissions' => $data['permissions']]);
            return response()->json([
                'message' => 'Acessos actualizados com sucesso.',
                'user' => $this->serializeUserAccess($user->fresh()->load(['roles', 'permissions', 'department'])),
            ]);
        });
    }

    private function ensureAdminHasAllPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $adminRole = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $adminRole->syncPermissions($allPermissions);

        $admin = User::where('email', self::ADMIN_EMAIL)->first();
        if ($admin) {
            $admin->syncRoles(Role::where('guard_name', 'web')->get());
            $admin->syncPermissions($allPermissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Serializa um perfil sem depender da relação polimórfica Role::users().
     * A contagem é obtida directamente da tabela pivot do Spatie.
     */
    private function serializeRole(Role $role): array
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');

        $usersCount = DB::table($table)
            ->where('role_id', $role->id)
            ->where('model_type', User::class)
            ->distinct('model_id')
            ->count('model_id');

        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->relationLoaded('permissions')
                ? $role->permissions
                : $role->permissions()->get(),
            'users_count' => $usersCount,
        ];
    }

    private function serializeUserAccess(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'active' => $user->active,
            'department' => $user->department,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getDirectPermissions()->pluck('name')->values(),
            'effective_permissions' => $user->getAllPermissions()->pluck('name')->unique()->values(),
            'is_system_admin' => strtolower($user->email) === self::ADMIN_EMAIL,
        ];
    }

    private function log(Request $request, string $action, string $entity, int $entityId, array $metadata = []): void
    {
        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }
}
