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
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
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
                'email' => 'required|string|email',
                'password' => 'required|string|min:8'
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
     * (Persona 2 debe completar esta funcionalidad)
     */
    public function refreshToken(Request $request)
    {
        // TODO: Implementar refresh token
        return response()->json([
            'message' => 'Refresh token endpoint - Por implementar',
            'status' => 501
        ], 501);
    }
}
