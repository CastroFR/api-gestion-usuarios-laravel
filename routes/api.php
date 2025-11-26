<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas PÚBLICAS
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas PROTEGIDAS
Route::middleware('auth:sanctum')->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

    // Gestión de Usuarios (CRUD)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}/restore', [UserController::class, 'restore']);

    // Estadísticas
    Route::get('/statistics/daily', [StatisticsController::class, 'dailyStatistics']);
    Route::get('/statistics/weekly', [StatisticsController::class, 'weeklyStatistics']);
    Route::get('/statistics/monthly', [StatisticsController::class, 'monthlyStatistics']);
    Route::get('/statistics/detailed', [StatisticsController::class, 'detailedStatistics']);
});