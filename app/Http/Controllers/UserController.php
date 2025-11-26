<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        //
        try {
            $users = User::select('id', 'name', 'email', 'created_at', 'updated_at')->get();

            return response()->json([
                'message' => 'Usuarios obtenidos exitosamente',
                'data' => $users,
                'total' => $users->count(),
                'status' => 200
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al obtener usuarios',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Mostrar usuario específico.
     */
    public function show(string $id)
    {
        //
        try {
            $user = User::select('id', 'name', 'email', 'created_at', 'updated_at')
                ->findOrFail($id);

            return response()->json([
                'message' => 'Usuario encontrado',
                'data' => $user,
                'status' => 200
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'error' => $error->getMessage()
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Actualizar usuario.
     */
    public function update(Request $request, string $id)
    {
        //
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|min:2',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only('name', 'email');

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user->only('id', 'name', 'email', 'updated_at'),
                'status' => 200
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al actualizar usuario',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario (Soft Delete).
     */
    public function destroy(string $id)
    {
        //
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado exitosamente',
                'status' => 200
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al eliminar usuario',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar usuario eliminado
     */
    public function restore(string $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            return response()->json([
                'message' => 'Usuario restaurado exitosamente',
                'data' => $user->only('id', 'name', 'email', 'updated_at'),
                'status' => 200
            ], 200);

        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al restaurar usuario',
                'error' => $error->getMessage()
            ], 500);
        }
    }
}
