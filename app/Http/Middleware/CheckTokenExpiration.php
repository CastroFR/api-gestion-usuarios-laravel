<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $token = $request->user()->currentAccessToken();
            
            // Verificar si el token ha expirado (5 minutos)
            if ($token->created_at->addMinutes(5)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expirado. Por favor, refresque el token.',
                    'code' => 'token_expired'
                ], 401);
            }
        }

        return $next($request);
    }
}
