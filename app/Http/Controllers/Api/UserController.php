<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return User::with([
            'department',
            'roles',
            'permissions'
        ])
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([

            'name' => ['required', 'string', 'max:255'],

            'email' => ['required', 'email', 'unique:users,email'],

            'role' => ['required', 'exists:roles,name'],

            'department_id' => ['nullable', 'integer', 'exists:departments,id'],

            'active' => ['boolean'],

            'password' => ['required', 'min:6'],

        ]);

        return DB::transaction(function () use ($data, $request) {

            $user = User::create([

                'name' => $data['name'],

                'email' => $data['email'],

                'department_id' => $data['department_id'],

                'active' => $data['active'] ?? true,

                'password' => Hash::make($data['password'])

            ]);

            $user->assignRole($data['role']);

            ActivityLog::create([

                'user_id' => $request->user()?->id,

                'action' => 'Criou utilizador',

                'entity' => User::class,

                'entity_id' => $user->id,

                'metadata' => [

                    'nome' => $user->name,

                    'perfil' => $data['role']

                ]

            ]);

            return [

                'user' => $user->load('department'),

                'roles' => $user->getRoleNames(),

                'permissions' => $user->getAllPermissions()->pluck('name')

            ];
        });
    }

    public function show(User $user)
    {
        return [

            'user' => $user->load('department'),

            'roles' => $user->getRoleNames(),

            'permissions' => $user->getAllPermissions()->pluck('name')

        ];
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([

            'name' => ['sometimes', 'required', 'string'],

            'email' => [
                'sometimes',
                'required',
                'email',
                'unique:users,email,' . $user->id
            ],

            'role' => [
                'sometimes',
                'required',
                'exists:roles,name'
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id'
            ],

            'active' => ['boolean'],

            'password' => ['nullable', 'min:6']

        ]);

        return DB::transaction(function () use ($data, $user, $request) {

            if (isset($data['password'])) {

                $data['password'] = Hash::make($data['password']);
            } else {

                unset($data['password']);
            }

            $user->update($data);

            if (isset($data['role'])) {

                $user->syncRoles([
                    $data['role']
                ]);
            }

            ActivityLog::create([

                'user_id' => $request->user()->id,

                'action' => 'Actualizou utilizador',

                'entity' => User::class,

                'entity_id' => $user->id,

                'metadata' => [

                    'nome' => $user->name

                ]

            ]);

            return [

                'user' => $user->fresh()->load('department'),

                'roles' => $user->getRoleNames(),

                'permissions' => $user->getAllPermissions()
                    ->pluck('name')

            ];
        });
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id == $user->id) {

            throw ValidationException::withMessages([

                'user' => [
                    'Não é permitido eliminar o próprio utilizador.'
                ]

            ]);
        }

        ActivityLog::create([

            'user_id' => $request->user()->id,

            'action' => 'Eliminou utilizador',

            'entity' => User::class,

            'entity_id' => $user->id,

            'metadata' => [

                'nome' => $user->name

            ]

        ]);

        $user->delete();

        return response()->noContent();
    }
}
