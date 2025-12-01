<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta pública de salud (para monitoreo)
Route::get('/health', function () {
    $status = 'healthy';
    $messages = [];

    // Verificar conexión a base de datos
    try {
        DB::connection()->getPdo();
        $messages[] = 'Database: OK';
    } catch (\Exception $e) {
        $status = 'unhealthy';
        $messages[] = 'Database: ERROR - ' . $e->getMessage();
    }

    // Verificar tiempo de actividad
    $uptime = exec('echo $SECONDS') ?: 'N/A';

    return response()->json([
        'status' => $status,
        'service' => 'API Gestión de Usuarios',
        'version' => '1.0.0',
        'environment' => app()->environment(),
        'timestamp' => now()->toDateTimeString(),
        'uptime' => $uptime . ' seconds',
        'checks' => $messages,
        'endpoints' => [
            'auth' => '/api/register, /api/login',
            'users' => '/api/users',
            'statistics' => '/api/statistics/*'
        ]
    ]);
});

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con autenticación y verificación de token
Route::middleware(['auth:sanctum', 'token.expiration'])->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    // Usuarios CRUD
    Route::apiResource('users', UserController::class);

    // Rutas adicionales para usuarios
    Route::post('/users/{user}/restore', [UserController::class, 'restore']);
    Route::delete('/users/{user}/force', [UserController::class, 'forceDelete']);

    // Estadísticas
    Route::prefix('statistics')->group(function () {
        Route::get('/daily', [StatisticsController::class, 'daily']);
        Route::get('/weekly', [StatisticsController::class, 'weekly']);
        Route::get('/monthly', [StatisticsController::class, 'monthly']);
        Route::get('/summary', [StatisticsController::class, 'summary']);
    });
});
