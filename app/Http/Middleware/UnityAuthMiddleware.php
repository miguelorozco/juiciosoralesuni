<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\User;

class UnityAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Acepta JWT o token unity_entry (base64 JSON con user_id, session_id, expires_at, type).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);
        if ($token !== null) {
            $token = trim($token);
        }
        if (!$token) {
            Log::debug('Unity auth: sin token en la request', [
                'path' => $request->path(),
                'has_auth_header' => $request->hasHeader('Authorization'),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticación requerido',
                'error_code' => 'MISSING_TOKEN'
            ], 401);
        }

        $authResult = $this->authenticateWithJwt($token) ?? $this->authenticateWithUnityEntryToken($token);

        $user = $authResult['user'] ?? $authResult;
        if (!$user instanceof User) {
            Log::debug('Unity auth: JWT y unity_entry fallaron', [
                'path' => $request->path(),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o usuario no encontrado',
                'error_code' => 'INVALID_TOKEN'
            ], 401);
        }

        if (is_array($authResult)) {
            Log::debug('Unity auth: autenticado con token unity_entry', [
                'user_id' => $user->id,
                'session_id' => $authResult['session_id'] ?? null,
            ]);
        }

        if (!$user->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo',
                'error_code' => 'USER_INACTIVE'
            ], 403);
        }

        $merge = [
            'unity_user' => $user,
            'unity_platform' => $request->header('X-Unity-Platform', 'Unknown'),
            'unity_version' => $request->header('X-Unity-Version', 'Unknown'),
        ];
        if (is_array($authResult) && isset($authResult['session_id'])) {
            $merge['unity_session_id'] = $authResult['session_id'];
        }
        $request->merge($merge);

        return $next($request);
    }

    /**
     * Intenta autenticar con JWT. Devuelve User o null.
     */
    private function authenticateWithJwt(string $token): User|null
    {
        try {
            $user = JWTAuth::setToken($token)->authenticate();
            return $user instanceof User ? $user : null;
        } catch (TokenExpiredException|TokenInvalidException|JWTException $e) {
            return null;
        }
    }

    /**
     * Intenta autenticar con token unity_entry (base64 JSON).
     * Mismo formato que UnityEntryController::generateAccessToken.
     * Devuelve ['user' => User, 'session_id' => int] o null.
     */
    private function authenticateWithUnityEntryToken(string $token): array|null
    {
        $token = trim($token);
        // Aceptar base64 estándar y URL-safe ( - y _ en lugar de + y /)
        $tokenNormalized = strtr($token, '-_', '+/');
        $decoded = @base64_decode($tokenNormalized, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)
            || ($payload['type'] ?? '') !== 'unity_entry'
            || empty($payload['user_id'])
            || !isset($payload['expires_at'])
        ) {
            return null;
        }

        if ((int) $payload['expires_at'] < time()) {
            return null;
        }

        $user = User::find($payload['user_id']);
        if (!$user) {
            return null;
        }

        return [
            'user' => $user,
            'session_id' => (int) ($payload['session_id'] ?? 0),
        ];
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