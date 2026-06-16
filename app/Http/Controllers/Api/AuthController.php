<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
                'email' => ['As credenciais informadas nao sao validas.'],
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Este utilizador esta inactivo.'],
            ]);
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Iniciou sessao',
            'entity' => 'User',
            'entity_id' => $user->id,
            'metadata' => ['ip' => $request->ip()],
        ]);

        return response()->json([
            'token' => $user->createToken('sigclop')->plainTextToken,
            'user' => $user->load('department'),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('department'));
    }

    public function logout(Request $request)
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Terminou sessao',
            'entity' => 'User',
            'entity_id' => $request->user()->id,
            'metadata' => ['ip' => $request->ip()],
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessao terminada com sucesso.']);
    }
}
