<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckTokenExpiration;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // =========== GRUPO API (para rutas en routes/api.php) ===========
        $middleware->group('api', [
            // 1. CORS - SIEMPRE primero para permitir peticiones cross-origin
            \Illuminate\Http\Middleware\HandleCors::class,

            // 2. Sustitución de bindings
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

        ]);

        // =========== ALIASES ===========
        $middleware->alias([
            'token.expiration' => CheckTokenExpiration::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Excepciones de Autenticación
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado',
                    'error' => 'unauthorized'
                ], 401);
            }
        });

        // Excepciones de Autorización
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado',
                    'error' => 'forbidden'
                ], 403);
            }
        });

        // Rutas no encontradas
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado',
                    'error' => 'not_found'
                ], 404);
            }
        });

        // Validaciones fallidas
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $e->errors()
                ], 422);
            }
        });

        // Errores generales
        $exceptions->render(function (\Exception $e, Request $request) {
            Log::error('API Error: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->is('api/*')) {
                $response = [
                    'success' => false,
                    'message' => 'Error interno del servidor',
                    'error' => 'server_error'
                ];

                return response()->json($response, 500);
            }
        });
    })
    ->create();
