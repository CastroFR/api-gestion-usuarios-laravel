<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        $users = User::withTrashed()->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Mostrar usuario específico
     */
    public function show($id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Crear nuevo usuario
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => $user
        ], 201);
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => ['sometimes', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only('name', 'email');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => $user
        ]);
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }

    /**
     * Restaurar usuario eliminado
     */
    public function restore($id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        if (!$user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no está eliminado'
            ], 400);
        }

        $user->restore();

        return response()->json([
            'success' => true,
            'message' => 'Usuario restaurado exitosamente',
            'data' => $user
        ]);
    }

    /**
     * Eliminar permanentemente
     */
    public function forceDelete($id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado permanentemente'
        ]);
    }
}
