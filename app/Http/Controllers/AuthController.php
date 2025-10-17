<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct()
    {
        // El middleware se aplica en las rutas, no en el constructor
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Iniciar sesión",
     *     description="Autentica un usuario y devuelve un token JWT",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(ref="#/components/schemas/LoginResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales inválidas",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de validación incorrectos",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorValidation")
     *     )
     * )
     * 
     * Get a JWT via given credentials.
     */
    /**
     * Get a JWT via given credentials.
     */
    public function login(Request $request): JsonResponse
    {
        Log::info('=== INICIO DEL PROCESO DE LOGIN ===');
        Log::info('IP: ' . $request->ip());
        Log::info('User Agent: ' . $request->userAgent());
        Log::info('Request Data: ' . json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            Log::warning('Validación fallida: ' . json_encode($validator->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $password = $request->input('password');
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        Log::info("Intentando login para email: $email desde IP: $ip");

        // Verificar si la IP está bloqueada
        if (LoginAttempt::isIpBlocked($ip)) {
            Log::warning("IP bloqueada: $ip");
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos fallidos desde esta dirección IP. Intenta más tarde.'
            ], 429);
        }

        // Verificar si el email está bloqueado
        if (LoginAttempt::isEmailBlocked($email)) {
            Log::warning("Email bloqueado: $email");
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos fallidos para esta cuenta. Intenta más tarde.'
            ], 429);
        }

        $credentials = $request->only('email', 'password');

        try {
            Log::info('Intentando autenticación JWT...');
            if (!$token = JWTAuth::attempt($credentials)) {
                Log::warning("Credenciales inválidas para email: $email");
                // Registrar intento fallido
                LoginAttempt::recordAttempt($email, $ip, $userAgent, false);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }
            Log::info('Token JWT generado exitosamente');
        } catch (JWTException $e) {
            Log::error('Error JWT: ' . $e->getMessage());
            // Registrar intento fallido
            LoginAttempt::recordAttempt($email, $ip, $userAgent, false);
            
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el token'
            ], 500);
        }

        // Registrar intento exitoso
        LoginAttempt::recordAttempt($email, $ip, $userAgent, true);

        $user = Auth::user();
        Log::info('Usuario autenticado: ' . json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tipo' => $user->tipo
        ]));

        // Actualizar último acceso
        $user->update(['ultimo_acceso' => now()]);
        Log::info('Último acceso actualizado');

        Log::info('=== LOGIN EXITOSO ===');
        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $user
            ],
            'message' => 'Login exitoso'
        ]);
    }

    /**
     * Login para frontend web (sin JWT)
     */
    public function loginWeb(Request $request): JsonResponse
    {
        Log::info('=== INICIO LOGIN WEB ===');
        Log::info('IP: ' . $request->ip());
        Log::info('User Agent: ' . $request->userAgent());
        Log::info('Request Data: ' . json_encode($request->all()));

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        Log::info('Validación pasada correctamente');

        // Usar JWT para autenticación web (más confiable que sesiones)
        $credentials = $request->only('email', 'password');
        Log::info('Intentando JWT::attempt con credenciales: ' . json_encode($credentials));
        
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                Log::warning('JWT::attempt falló - credenciales inválidas');
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }
            
            Log::info('JWT::attempt exitoso');
            
            $user = Auth::user();
            Log::info('Usuario autenticado: ' . json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tipo' => $user->tipo
            ]));
            
            // Actualizar último acceso
            $user->update(['ultimo_acceso' => now()]);
            Log::info('Último acceso actualizado');
            
            Log::info('=== LOGIN WEB EXITOSO ===');
            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tipo' => $user->tipo,
                ]
            ]);
            
        } catch (JWTException $e) {
            Log::error('Error JWT: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Registrar usuario",
     *     description="Registra un nuevo usuario en el sistema",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario registrado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de validación incorrectos",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorValidation")
     *     )
     * )
     * 
     * Register a User.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Si llegamos aquí, significa que el registro está habilitado
        $user = User::create([
            'name' => $request->name,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tipo' => $request->tipo,
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Usuario registrado exitosamente'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Obtener información del usuario autenticado",
     *     description="Retorna la información del usuario autenticado usando el token JWT",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Información del usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan@example.com"),
     *                 @OA\Property(property="tipo", type="string", example="instructor")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * 
     * Get the authenticated user.
     */
    public function me(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tipo' => $user->tipo,
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Cerrar sesión",
     *     description="Invalida el token JWT del usuario",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout exitoso",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * 
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo cerrar la sesión'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     summary="Renovar token",
     *     description="Renueva el token JWT del usuario",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token renovado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/LoginResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * 
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth()->factory()->getTTL() * 60,
                    'user' => auth()->user()
                ],
                'message' => 'Token renovado exitosamente'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo renovar el token'
            ], 500);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/auth/profile",
     *     summary="Actualizar perfil",
     *     description="Actualiza la información del usuario autenticado",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Juan"),
     *             @OA\Property(property="apellido", type="string", example="Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan.perez@ejemplo.com"),
     *             @OA\Property(property="password", type="string", format="password", example="nuevapassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Perfil actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string", example="Perfil actualizado exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de validación incorrectos",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorValidation")
     *     )
     * )
     * 
     * Update the authenticated User's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|between:2,100',
            'apellido' => 'sometimes|required|string|between:2,100',
            'email' => 'sometimes|required|string|email|max:100|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $validator->validated();
        
        if (isset($updateData['password'])) {
            $updateData['password'] = Hash::make($updateData['password']);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
            'message' => 'Perfil actualizado exitosamente'
        ]);
    }

    /**
     * Get the current registration status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRegistrationStatus()
    {
        $isEnabled = config('app.allow_new_user', true);
        
        return response()->json([
            'success' => true,
            'data' => [
                'registration_enabled' => $isEnabled,
                'message' => $isEnabled 
                    ? 'El registro de nuevos usuarios está habilitado' 
                    : 'El registro de nuevos usuarios no está habilitado'
            ]
        ]);
    }
}