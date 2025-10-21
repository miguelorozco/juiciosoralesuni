<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class UnityAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Obtener token del header Authorization o de Unity headers personalizados
            $token = $this->getTokenFromRequest($request);
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autenticación requerido',
                    'error_code' => 'MISSING_TOKEN'
                ], 401);
            }

            // Verificar y autenticar el token
            $user = JWTAuth::setToken($token)->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o usuario no encontrado',
                    'error_code' => 'INVALID_TOKEN'
                ], 401);
            }

            // Verificar que el usuario esté activo
            if (!$user->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo',
                    'error_code' => 'USER_INACTIVE'
                ], 403);
            }

            // Agregar información de Unity a la request
            $request->merge([
                'unity_user' => $user,
                'unity_platform' => $request->header('X-Unity-Platform', 'Unknown'),
                'unity_version' => $request->header('X-Unity-Version', 'Unknown'),
            ]);

            return $next($request);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado',
                'error_code' => 'TOKEN_EXPIRED',
                'refresh_required' => true
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
                'error_code' => 'TOKEN_INVALID'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación: ' . $e->getMessage(),
                'error_code' => 'AUTH_ERROR'
            ], 401);
        }
    }

    /**
     * Obtener token de la request desde diferentes fuentes
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // 1. Header Authorization estándar
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 2. Header personalizado de Unity
        $unityToken = $request->header('X-Unity-Token');
        if ($unityToken) {
            return $unityToken;
        }

        // 3. Query parameter (para casos especiales)
        $queryToken = $request->query('token');
        if ($queryToken) {
            return $queryToken;
        }

        // 4. Body parameter (para POST requests)
        $bodyToken = $request->input('token');
        if ($bodyToken) {
            return $bodyToken;
        }

        return null;
    }
}