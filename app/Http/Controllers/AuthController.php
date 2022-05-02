<?php

namespace App\Http\Controllers;
use App\Models\User;
use Auth;
use Carbon\Carbon;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Registro de usuario
     */
    public function signUp(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    /**
     * Inicio de sesión y creación de token
     */
    public function login(Request $request)
    {
        $user = User::where('codigo', $request->Codigo)->first();

        if ($user->Password == sha1($request->Password)){
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => [
                    'codigo' => $user->Codigo,
                    'descripcion' => $user->Descripcion
                ],
                'acces_token' => $token,
                'res' => true
            ], 200);
        }
        return response()->json([
            'message' => 'Usuario y/o contraseña es invalido.',
            'res' => false
        ], 401);
    }

    /**
     * Cierre de sesión (anular el token)
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function loginAuna(Request $request)
    {
        $user = User::where('codigo', $request->Codigo)->first();

        if ($user->Password == $request->Password){
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => [
                    'codigo' => $user->Codigo,
                    'descripcion' => $user->Descripcion
                ],
                'acces_token' => $token,
                'res' => true
            ], 200);
        }
        return response()->json([
            'message' => 'Usuario y/o contraseña es invalido.',
            'res' => false
        ], 401);
    }

}
