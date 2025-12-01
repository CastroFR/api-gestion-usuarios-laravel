<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::truncate(); // evitar duplicados al correr el seeder múltiples veces

        // Usuario administrador
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin123!'),
            'email_verified_at' => now(),
        ]);

        // Usuarios de prueba con fechas distribuidas para estadísticas
        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();

        for ($i = 1; $i <= 20; $i++) {
            $randomDate = Carbon::createFromTimestamp(
                rand($startDate->timestamp, $endDate->timestamp)
            );
            
            User::create([
                'name' => "Usuario {$i}",
                'email' => "usuario{$i}@example.com",
                'password' => Hash::make('Password123!'),
                'email_verified_at' => $randomDate,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        }

        // Usuarios eliminados (soft delete) para pruebas
        for ($i = 21; $i <= 25; $i++) {
            $user = User::create([
                'name' => "Usuario Eliminado {$i}",
                'email' => "eliminado{$i}@example.com",
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]);
            $user->delete(); // Soft delete
        }

        $this->command->info('✅ 26 usuarios creados (5 eliminados para pruebas)');
    }
}