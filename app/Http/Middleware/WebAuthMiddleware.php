<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('=== MIDDLEWARE WEB AUTH ===');
        Log::info('URL: ' . $request->url());
        Log::info('Method: ' . $request->method());
        Log::info('IP: ' . $request->ip());
        
        // Verificar si el usuario está autenticado en la sesión web
        $isAuthenticated = Auth::check();
        $user = Auth::user();
        
        Log::info('Auth::check(): ' . ($isAuthenticated ? 'true' : 'false'));
        Log::info('Auth::user(): ' . ($user ? json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'tipo' => $user->tipo
        ]) : 'null'));
        
        if ($isAuthenticated) {
            Log::info('Usuario autenticado, continuando...');
            return $next($request);
        }
        
        // Si no está autenticado, verificar si es una petición AJAX con token
        if ($request->ajax()) {
            $token = $request->header('Authorization');
            if ($token) {
                $token = str_replace('Bearer ', '', $token);
                Log::info('Token encontrado en header AJAX');
                
                try {
                    $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                    if ($user) {
                        Log::info('Token válido, usuario: ' . $user->email);
                        Auth::login($user);
                        Log::info('Usuario autenticado en sesión web');
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    Log::error('Error verificando token AJAX: ' . $e->getMessage());
                }
            }
        }
        
        // Verificar si hay token en el header Authorization (para navegación normal con token)
        $token = $request->header('Authorization');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            Log::info('Token encontrado en header de navegación normal');
            
            try {
                $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                if ($user) {
                    Log::info('Token válido, usuario: ' . $user->email);
                    Auth::login($user);
                    Log::info('Usuario autenticado en sesión web');
                    return $next($request);
                }
            } catch (\Exception $e) {
                Log::error('Error verificando token: ' . $e->getMessage());
            }
        }
        
        Log::info('Usuario NO autenticado, redirigiendo a login');
        return redirect()->route('login');
    }
}
