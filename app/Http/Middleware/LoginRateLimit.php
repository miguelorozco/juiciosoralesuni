<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LoginRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'login:' . $request->ip();
        
        // Permitir 5 intentos por minuto por IP
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos de login. Intenta de nuevo en ' . $seconds . ' segundos.',
                'retry_after' => $seconds
            ], 429);
        }

        // Incrementar contador de intentos
        RateLimiter::hit($key, 60); // 60 segundos de bloqueo

        return $next($request);
    }
}
