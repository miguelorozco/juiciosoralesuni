<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;

class UnityAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/unity/auth/login",
     *     summary="Login para Unity",
     *     description="Autenticación específica para aplicaciones Unity con información adicional",
     *     tags={"Unity - Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="alumno@example.com"),
     *             @OA\Property(property="password", type="string", example="password"),
     *             @OA\Property(property="unity_version", type="string", example="2022.3.15f1"),
     *             @OA\Property(property="unity_platform", type="string", example="WindowsPlayer"),
     *             @OA\Property(property="device_id", type="string", example="UNITY_DEVICE_123"),
     *             @OA\Property(property="session_data", type="object", description="Datos adicionales de la sesión Unity")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login exitoso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="unity_info", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'unity_version' => 'nullable|string|max:50',
                'unity_platform' => 'nullable|string|max:50',
                'device_id' => 'nullable|string|max:100',
                'session_data' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                    'error_code' => 'VALIDATION_ERROR'
                ], 422);
            }

            $credentials = $request->only('email', 'password');
            $unityInfo = [
                'unity_version' => $request->input('unity_version', 'Unknown'),
                'unity_platform' => $request->input('unity_platform', 'Unknown'),
                'device_id' => $request->input('device_id', 'Unknown'),
                'session_data' => $request->input('session_data', []),
                'login_timestamp' => now()->toISOString(),
            ];

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas',
                    'error_code' => 'INVALID_CREDENTIALS'
                ], 401);
            }

            $user = Auth::user();

            // Verificar que el usuario esté activo
            if (!$user->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo',
                    'error_code' => 'USER_INACTIVE'
                ], 403);
            }

            // Actualizar información de Unity en el usuario
            $userConfig = $user->configuracion ?? [];
            $userConfig['unity_info'] = $unityInfo;
            $user->update(['configuracion' => $userConfig]);

            // Preparar respuesta para Unity
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'tipo' => $user->tipo,
                'activo' => $user->activo,
                'configuracion' => $user->configuracion,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60, // en segundos
                    'user' => $userData,
                    'unity_info' => $unityInfo,
                    'server_time' => now()->toISOString(),
                ]
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar token: ' . $e->getMessage(),
                'error_code' => 'TOKEN_GENERATION_ERROR'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/unity/auth/refresh",
     *     summary="Refresh token para Unity",
     *     description="Renovar token JWT para mantener la sesión activa",
     *     tags={"Unity - Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token renovado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token renovado exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     )
     * )
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh();
            $user = JWTAuth::setToken($token)->toUser();

            return response()->json([
                'success' => true,
                'message' => 'Token renovado exitosamente',
                'data' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'apellido' => $user->apellido,
                        'email' => $user->email,
                        'tipo' => $user->tipo,
                    ],
                    'server_time' => now()->toISOString(),
                ]
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo renovar el token: ' . $e->getMessage(),
                'error_code' => 'REFRESH_ERROR'
            ], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/unity/auth/logout",
     *     summary="Logout para Unity",
     *     description="Cerrar sesión y invalidar token",
     *     tags={"Unity - Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout exitoso")
     *         )
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate();

            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso',
                'data' => [
                    'server_time' => now()->toISOString(),
                ]
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión: ' . $e->getMessage(),
                'error_code' => 'LOGOUT_ERROR'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/unity/auth/me",
     *     summary="Información del usuario actual",
     *     description="Obtener información del usuario autenticado",
     *     tags={"Unity - Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Información obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'apellido' => $user->apellido,
                        'email' => $user->email,
                        'tipo' => $user->tipo,
                        'activo' => $user->activo,
                        'configuracion' => $user->configuracion,
                    ],
                    'server_time' => now()->toISOString(),
                ]
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage(),
                'error_code' => 'INVALID_TOKEN'
            ], 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/unity/auth/status",
     *     summary="Estado de la conexión Unity",
     *     description="Verificar estado de la conexión y obtener información del servidor",
     *     tags={"Unity - Autenticación"},
     *     @OA\Response(
     *         response=200,
     *         description="Estado obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="server_status", type="string", example="online"),
     *                 @OA\Property(property="api_version", type="string", example="1.0.0"),
     *                 @OA\Property(property="server_time", type="string", example="2025-01-15T10:30:00Z")
     *             )
     *         )
     *     )
     * )
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'server_status' => 'online',
                'api_version' => '1.0.0',
                'unity_support' => true,
                'server_time' => now()->toISOString(),
                'timezone' => config('app.timezone'),
                'features' => [
                    'real_time_communication' => true,
                    'dialog_system' => true,
                    'character_movements' => true,
                    'session_management' => true,
                ]
            ],
            'message' => 'Servidor Unity disponible'
        ]);
    }
}