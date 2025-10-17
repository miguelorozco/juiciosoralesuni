<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class PreventUserEnumeration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en rutas de login
        if ($request->is('api/auth/login')) {
            return $this->handleLogin($request, $next);
        }

        return $next($request);
    }

    private function handleLogin(Request $request, Closure $next): Response
    {
        $email = $request->input('email');
        $password = $request->input('password');

        // Siempre verificar la contraseña, incluso si el usuario no existe
        // Esto previene la enumeración de usuarios
        $user = User::where('email', $email)->first();
        
        // Si el usuario existe, verificar la contraseña real
        if ($user) {
            // Verificar que la contraseña esté hasheada correctamente
            if (strlen($user->password) < 60 || !str_starts_with($user->password, '$2y$')) {
                // Si la contraseña no está hasheada, crear una respuesta de error
                return $this->createFailedLoginResponse();
            }
            
            if (!Hash::check($password, $user->password)) {
                return $this->createFailedLoginResponse();
            }
            
            // Si la contraseña es correcta, continuar con el login normal
            return $next($request);
        } else {
            // Si el usuario no existe, hacer una verificación falsa para mantener el tiempo de respuesta
            Hash::check($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
            return $this->createFailedLoginResponse();
        }
    }

    private function createFailedLoginResponse(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ], 401);
    }
}
