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
        
        // Verificar si hay token en el header Authorization
        $token = $request->header('Authorization');
        Log::info('Authorization header: ' . ($token ? 'present' : 'missing'));
        
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            Log::info('Token encontrado, verificando...');
            
            try {
                $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                if ($user) {
                    Log::info('Token válido, usuario: ' . $user->email);
                    Auth::guard('web')->login($user);
                    Log::info('Usuario autenticado en sesión web desde token');
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
        // Para rutas web, usar autenticación de sesión normal
        $isAuthenticated = Auth::check();
        $user = Auth::user();
        
        Log::info('=== HANDLE WEB AUTH ===');
        Log::info('Auth::check(): ' . ($isAuthenticated ? 'true' : 'false'));
        Log::info('Auth::user(): ' . ($user ? json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tipo' => $user->tipo,
            'activo' => $user->activo
        ]) : 'null'));
        Log::info('Has Session: ' . ($request->hasSession() ? 'true' : 'false'));
        
        if ($isAuthenticated && $user) {
            // Verificar que el usuario esté activo
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
