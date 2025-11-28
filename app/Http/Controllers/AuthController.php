<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:50|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
                'email' => 'required|email:rfc,dns|max:255|unique:users,email',
                'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',    // al menos 1 mayúscula
                'regex:/[a-z]/',    // al menos 1 minúscula
                'regex:/[0-9]/',    // al menos 1 número
                'regex:/[@$!%*?&]/' // al menos un símbolo
    ]
]);


            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => $user,
                'status' => 201
            ], 201);

        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Login de usuario - TOKEN de 5 MINUTOS
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email:rfc',
                'password' => 'required|string|min:8',
]);


            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = $request->user();
                
                // TOKEN de 5 MINUTOS como requiere el proyecto
                $expirationTime = Carbon::now()->addMinutes(5);
                
                $token = $user->createToken('auth_token', ['*'], $expirationTime)->plainTextToken;

                return response()->json([
                    'message' => 'Login exitoso',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'token_type' => 'Bearer',
                    'access_token' => $token,
                    'expires_at' => $expirationTime->toDateTimeString(),
                    'status' => 200
                ], 200);
            }

            return response()->json([
                'message' => 'Credenciales incorrectas',
                'status' => 401
            ], 401);

        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error en el login',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Logout - Revocar token actual
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout exitoso',
                'status' => 200
            ], 200);

        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error en el logout',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh token - PARA IMPLEMENTAR
     * (IMPLEMENTADO SEGÚN REQUERIMIENTO)
     */
 
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado',
                    'status' => 401
                ], 401);
            }

            // Revocar token actual
            $user->currentAccessToken()->delete();

            // Crear nuevo token (5 minutos)
            $expiration = Carbon::now()->addMinutes(5);
            $newToken = $user->createToken('auth_token', ['*'], $expiration)->plainTextToken;

            return response()->json([
                'message' => 'Token renovado exitosamente',
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiration->toDateTimeString(),
                'status' => 200
            ], 200);

        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al refrescar el token',
                'error' => $error->getMessage()
            ], 500);
        }
    }
}
