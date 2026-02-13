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
        // Excluir rutas de Unity de este middleware
        if (str_starts_with($request->path(), 'api/unity/')) {
            return $next($request);
        }
        
        Log::info('=== MIDDLEWARE WEB AUTH ===');
        Log::info('Path: ' . $request->path());
        Log::info('Full URL: ' . $request->fullUrl());
        Log::info('Method: ' . $request->method());
        Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));
        Log::info('Wants JSON: ' . ($request->wantsJson() ? 'true' : 'false'));
        Log::info('Expects JSON: ' . ($request->expectsJson() ? 'true' : 'false'));
        Log::info('Has Session: ' . ($request->hasSession() ? 'true' : 'false'));
        Log::info('Session ID: ' . ($request->hasSession() ? $request->session()->getId() : 'N/A'));
        Log::info('Cookies: ' . json_encode($request->cookies->all()));
        Log::info('Headers: ' . json_encode($request->headers->all()));
        
        // Para rutas API, verificar autenticación de sesión web
        if (str_starts_with($request->path(), 'api/')) {
            Log::info('Es ruta API, llamando handleApiAuth');
            return $this->handleApiAuth($request, $next);
        }
        
        // Para rutas web, usar autenticación de sesión normal
        Log::info('Es ruta web, llamando handleWebAuth');
        return $this->handleWebAuth($request, $next);
    }
    
    private function handleApiAuth(Request $request, Closure $next)
    {
        Log::info('=== HANDLE API AUTH ===');
        Log::info('Request path: ' . $request->path());
        Log::info('Has session: ' . ($request->hasSession() ? 'true' : 'false'));
        
        // Intentar iniciar sesión si no está iniciada
        if (!$request->hasSession()) {
            Log::info('No hay sesión disponible, intentando iniciar sesión...');
            // Las rutas API necesitan el middleware 'web' para tener acceso a la sesión
            // Si no hay sesión, intentar usar el guard 'web' directamente
        }
        
        // Verificar si el usuario está autenticado en la sesión web
        // Usar el guard 'web' explícitamente
        $isAuthenticated = Auth::guard('web')->check();
        $user = Auth::guard('web')->user();
        
        Log::info('Auth::guard("web")->check(): ' . ($isAuthenticated ? 'true' : 'false'));
        Log::info('Auth::guard("web")->user(): ' . ($user ? json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'tipo' => $user->tipo
        ]) : 'null'));
        
        if ($isAuthenticated) {
            Log::info('Usuario autenticado en sesión web, continuando...');
            return $next($request);
        }
        
        // Verificar token en header Authorization o en cookie (para fetch desde estadísticas con credentials)
        $token = $request->header('Authorization');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        } elseif ($request->cookie('web_auth_token')) {
            $token = $request->cookie('web_auth_token');
            Log::info('Token tomado de cookie web_auth_token para API');
        }
        
        if ($token) {
            Log::info('Token encontrado, verificando...');
            try {
                $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                if ($user && $user->activo) {
                    Log::info('Token válido, usuario: ' . $user->email);
                    Auth::guard('web')->login($user);
                    $request->session()->regenerate();
                    Log::info('Usuario autenticado en sesión web desde token (API)');
                    return $next($request);
                }
            } catch (\Exception $e) {
                Log::error('Error verificando token: ' . $e->getMessage());
            }
        }
        
        // Para todas las peticiones API, siempre devolver JSON, nunca redirigir
        Log::info('Usuario NO autenticado en API, devolviendo JSON 401');
        return response()->json([
            'success' => false,
            'message' => 'No autorizado',
            'error' => 'Sesión de autenticación requerida'
        ], 401);
    }
    
    private function handleWebAuth(Request $request, Closure $next)
    {
        $isAuthenticated = Auth::check();
        $user = Auth::user();

        if (!$isAuthenticated && $request->cookie('web_auth_token')) {
            try {
                $token = $request->cookie('web_auth_token');
                $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                if ($user && $user->activo) {
                    Auth::guard('web')->login($user, true);
                    $request->session()->regenerate();
                    Log::info('Usuario autenticado en ruta web desde cookie web_auth_token: ' . $user->email);
                    return $next($request);
                }
            } catch (\Exception $e) {
                Log::info('Token en cookie inválido o expirado: ' . $e->getMessage());
            }
        }
        
        if ($isAuthenticated && $user) {
            if (!$user->activo) {
                Log::warning('Usuario inactivo intentando acceder: ' . $user->email);
                Auth::logout();
                return redirect()->route('login')->with('error', 'Tu cuenta está inactiva');
            }
            Log::info('Usuario autenticado en sesión web, continuando...');
            return $next($request);
        }
        
        Log::info('Usuario NO autenticado en sesión web, redirigiendo a login');
        return redirect()->route('login')->with('error', 'Por favor inicia sesión para continuar');
    }
}
