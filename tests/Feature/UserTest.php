<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /** @test - Listar usuarios */
    public function test_can_list_users()
    {
        $auth = $this->authenticate();
        User::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'Accept' => 'application/json'
        ])->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'name', 'email', 'created_at']
                         ]
                     ]
                 ]);
    }

    /** @test - Mostrar usuario específico */
    public function test_can_show_user()
    {
        $auth = $this->authenticate();
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'Accept' => 'application/json'
        ])->getJson('/api/users/' . $user->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['id', 'name', 'email']
                 ]);
    }

    /** @test - Usuario no encontrado */
    public function test_returns_404_for_nonexistent_user()
    {
        $auth = $this->authenticate();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'Accept' => 'application/json'
        ])->getJson('/api/users/999');

        $response->assertStatus(404);
    }

    /** @test - Crear usuario */
    public function test_can_create_user()
    {
        $auth = $this->authenticate();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'Accept' => 'application/json'
        ])->postJson('/api/users', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Usuario creado exitosamente'
                 ]);
    }

    /** @test - Actualizar usuario */
    public function test_can_update_user()
    {
        $auth = $this->authenticate();
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'Accept' => 'application/json'
        ])->putJson('/api/users/' . $user->id, [
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.com'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Usuario actualizado exitosamente'
                 ]);
    }

    /** @test - Eliminar usuario (soft delete) */
    public function test_can_soft_delete_user()
    {
        $auth = $this->authenticate();
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'Accept' => 'application/json'
        ])->deleteJson('/api/users/' . $user->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Usuario eliminado exitosamente'
                 ]);

        // Verificar que el usuario está soft deleted
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test - Restaurar usuario */
    public function test_can_restore_user()
    {
        $auth = $this->authenticate();
        $user = User::factory()->create();
        $user->delete(); // Soft delete

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'Accept' => 'application/json'
        ])->postJson('/api/users/' . $user->id . '/restore');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Usuario restaurado exitosamente'
                 ]);
    }
}