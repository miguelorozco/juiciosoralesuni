<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\RolDisponibleController;
use App\Http\Controllers\PlantillaSesionController;
use App\Http\Controllers\SesionJuicioController;
use App\Http\Controllers\AsignacionRolController;
use App\Http\Controllers\ConfiguracionSistemaController;
use App\Http\Controllers\DialogoController;
use App\Http\Controllers\DialogoFlujoController;
use App\Http\Controllers\UnityDialogoController;
use App\Http\Controllers\UnityAuthController;
use App\Http\Controllers\UnityRealtimeController;
use App\Http\Controllers\UnityRoomController;
use App\Http\Controllers\NodoDialogoController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PanelDialogoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas de autenticación
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware(['login.rate.limit', 'prevent.user.enumeration']);
    Route::post('register', [AuthController::class, 'register'])->middleware('check.user.registration');
    Route::get('registration-status', [AuthController::class, 'getRegistrationStatus']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('cambiar-contraseña', [ProfileController::class, 'cambiarContraseña']);
    });
});

// Rutas protegidas por autenticación
Route::middleware('auth:api')->group(function () {
    
    // Rutas de prueba
    Route::get('/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Autenticación JWT funcionando correctamente',
            'user' => auth()->user()
        ]);
    });

    // ========================================
    // RUTAS DE ROLES DISPONIBLES
    // ========================================
    Route::group(['prefix' => 'roles'], function () {
        Route::get('/', [RolDisponibleController::class, 'index']);
        Route::post('/', [RolDisponibleController::class, 'store'])->middleware('user.type:admin,instructor');
        Route::get('/activos', [RolDisponibleController::class, 'activos']);
        Route::get('/{rolDisponible}', [RolDisponibleController::class, 'show']);
        Route::put('/{rolDisponible}', [RolDisponibleController::class, 'update'])->middleware('user.type:admin,instructor');
        Route::delete('/{rolDisponible}', [RolDisponibleController::class, 'destroy'])->middleware('user.type:admin');
        Route::post('/{rolDisponible}/toggle-activo', [RolDisponibleController::class, 'toggleActivo'])->middleware('user.type:admin,instructor');
        Route::post('/reordenar', [RolDisponibleController::class, 'reordenar'])->middleware('user.type:admin,instructor');
    });

    // ========================================
    // RUTAS DE PLANTILLAS DE SESIONES
    // ========================================
    Route::group(['prefix' => 'plantillas'], function () {
        Route::get('/', [PlantillaSesionController::class, 'index']);
        Route::post('/', [PlantillaSesionController::class, 'store'])->middleware('user.type:admin,instructor');
        Route::get('/publicas', [PlantillaSesionController::class, 'publicas']);
        Route::get('/mis-plantillas', [PlantillaSesionController::class, 'misPlantillas']);
        Route::get('/{plantillaSesion}', [PlantillaSesionController::class, 'show']);
        Route::put('/{plantillaSesion}', [PlantillaSesionController::class, 'update'])->middleware('user.type:admin,instructor');
        Route::delete('/{plantillaSesion}', [PlantillaSesionController::class, 'destroy'])->middleware('user.type:admin,instructor');
        
        // Endpoints especializados de plantillas
        Route::post('/{plantillaSesion}/agregar-rol', [PlantillaSesionController::class, 'agregarRol'])->middleware('user.type:admin,instructor');
        Route::post('/{plantillaSesion}/remover-rol', [PlantillaSesionController::class, 'removerRol'])->middleware('user.type:admin,instructor');
        Route::post('/{plantillaSesion}/crear-sesion', [PlantillaSesionController::class, 'crearSesion'])->middleware('user.type:admin,instructor');
    });

    // ========================================
    // RUTAS DE SESIONES DE JUICIOS
    // ========================================
    Route::group(['prefix' => 'sesiones'], function () {
        Route::get('/', [SesionJuicioController::class, 'index']);
        Route::post('/', [SesionJuicioController::class, 'store'])->middleware('user.type:admin,instructor');
        Route::get('/activas', [SesionJuicioController::class, 'activas']);
        Route::get('/mis-sesiones', [SesionJuicioController::class, 'misSesiones']);
        Route::get('/{sesionJuicio}', [SesionJuicioController::class, 'show']);
        Route::put('/{sesionJuicio}', [SesionJuicioController::class, 'update'])->middleware('user.type:admin,instructor');
        Route::delete('/{sesionJuicio}', [SesionJuicioController::class, 'destroy'])->middleware('user.type:admin,instructor');
        
        // Endpoints especializados de sesiones
        Route::post('/{sesionJuicio}/iniciar', [SesionJuicioController::class, 'iniciar'])->middleware('user.type:admin,instructor');
        Route::post('/{sesionJuicio}/finalizar', [SesionJuicioController::class, 'finalizar'])->middleware('user.type:admin,instructor');
        Route::post('/{sesionJuicio}/cancelar', [SesionJuicioController::class, 'cancelar'])->middleware('user.type:admin,instructor');
        Route::post('/{sesionJuicio}/agregar-participante', [SesionJuicioController::class, 'agregarParticipante'])->middleware('user.type:admin,instructor');
        Route::post('/{sesionJuicio}/remover-participante', [SesionJuicioController::class, 'removerParticipante'])->middleware('user.type:admin,instructor');
        Route::post('/{sesionJuicio}/generar-room-id', [SesionJuicioController::class, 'generarRoomId'])->middleware('user.type:admin,instructor');
    });

    // ========================================
    // RUTAS DE ASIGNACIONES DE ROLES
    // ========================================
    Route::group(['prefix' => 'asignaciones'], function () {
        Route::get('/', [AsignacionRolController::class, 'index']);
        Route::post('/', [AsignacionRolController::class, 'store'])->middleware('user.type:admin,instructor');
        Route::get('/pendientes', [AsignacionRolController::class, 'pendientes']);
        Route::get('/mis-asignaciones', [AsignacionRolController::class, 'misAsignaciones']);
        Route::get('/por-sesion/{sesionJuicio}', [AsignacionRolController::class, 'porSesion']);
        Route::get('/{asignacionRol}', [AsignacionRolController::class, 'show']);
        Route::put('/{asignacionRol}', [AsignacionRolController::class, 'update'])->middleware('user.type:admin,instructor');
        Route::delete('/{asignacionRol}', [AsignacionRolController::class, 'destroy'])->middleware('user.type:admin,instructor');
        
        // Endpoints especializados de asignaciones
        Route::post('/{asignacionRol}/confirmar', [AsignacionRolController::class, 'confirmar']);
        Route::post('/{asignacionRol}/desconfirmar', [AsignacionRolController::class, 'desconfirmar'])->middleware('user.type:admin,instructor');
        Route::post('/{asignacionRol}/cambiar-rol', [AsignacionRolController::class, 'cambiarRol'])->middleware('user.type:admin,instructor');
        Route::post('/{asignacionRol}/actualizar-notas', [AsignacionRolController::class, 'actualizarNotas'])->middleware('user.type:admin,instructor');
    });

    // ========================================
    // RUTAS DE CONFIGURACIONES DEL SISTEMA
    // ========================================
    Route::group(['prefix' => 'configuraciones'], function () {
        Route::get('/', [ConfiguracionSistemaController::class, 'index'])->middleware('user.type:admin');
        Route::post('/', [ConfiguracionSistemaController::class, 'store'])->middleware('user.type:admin');
        Route::get('/todas', [ConfiguracionSistemaController::class, 'todas']);
        Route::get('/sistema', [ConfiguracionSistemaController::class, 'sistema']);
        Route::get('/obtener', [ConfiguracionSistemaController::class, 'obtener']);
        Route::post('/establecer', [ConfiguracionSistemaController::class, 'establecer'])->middleware('user.type:admin');
        Route::post('/actualizar-multiples', [ConfiguracionSistemaController::class, 'actualizarMultiples'])->middleware('user.type:admin');
        Route::get('/por-tipo', [ConfiguracionSistemaController::class, 'porTipo']);
        Route::get('/{configuracionSistema}', [ConfiguracionSistemaController::class, 'show'])->middleware('user.type:admin');
        Route::put('/{configuracionSistema}', [ConfiguracionSistemaController::class, 'update'])->middleware('user.type:admin');
        Route::delete('/{configuracionSistema}', [ConfiguracionSistemaController::class, 'destroy'])->middleware('user.type:admin');
        
        // Nuevas rutas de configuración
        Route::post('/limpiar-cache', [ConfiguracionController::class, 'limpiarCache'])->middleware('user.type:admin');
        Route::post('/regenerar-logs', [ConfiguracionController::class, 'regenerarLogs'])->middleware('user.type:admin');
        Route::post('/probar-unity', [ConfiguracionController::class, 'probarUnity'])->middleware('user.type:admin');
        Route::post('/reiniciar-sistema', [ConfiguracionController::class, 'reiniciarSistema'])->middleware('user.type:admin');
    });

    // ========================================
    // RUTAS DE USUARIOS (ADMINISTRACIÓN)
    // ========================================
    Route::group(['prefix' => 'usuarios'], function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::get('/estudiantes', [UsuarioController::class, 'estudiantes']);
        Route::get('/instructores', [UsuarioController::class, 'instructores']);
        Route::get('/{usuario}', [UsuarioController::class, 'show']);
    });

    // ========================================
    // RUTAS DE ADMINISTRACIÓN DE USUARIOS
    // ========================================
    Route::middleware('user.type:admin')->group(function () {
        Route::post('admin/create-user', [AdminUserController::class, 'createUser']);
        Route::post('admin/toggle-registration', [AdminUserController::class, 'toggleRegistrationStatus']);
    });

    // ========================================
    // RUTAS DE ESTADÍSTICAS Y REPORTES
    // ========================================
    Route::group(['prefix' => 'estadisticas'], function () {
        Route::get('/dashboard', [EstadisticasController::class, 'dashboard']);
        Route::get('/top-instructores', [EstadisticasController::class, 'topInstructores']);
        Route::get('/actividad-reciente', [EstadisticasController::class, 'actividadReciente']);
        Route::get('/usuario', [EstadisticasController::class, 'usuario']);
        Route::get('/actividad-usuario', [EstadisticasController::class, 'actividadUsuario']);
    });

    // ========================================
    // RUTAS DE DIÁLOGOS RAMIFICADOS (v2)
    // ========================================
    // NOTA: Estas rutas usan DialogoController que ahora usa DialogoV2 internamente
    // TODO: Crear DialogoV2Controller específico en el futuro
    Route::group(['prefix' => 'dialogos'], function () {
        Route::get('/', [DialogoController::class, 'index']);
        Route::post('/', [DialogoController::class, 'store'])->middleware('user.type:admin,instructor');
        Route::post('/import', [App\Http\Controllers\DialogoImportController::class, 'importar'])->middleware('user.type:admin,instructor');
        Route::get('/{dialogo}', [DialogoController::class, 'show']);
        Route::put('/{dialogo}', [DialogoController::class, 'update'])->middleware('user.type:admin,instructor');
        Route::delete('/{dialogo}', [DialogoController::class, 'destroy'])->middleware('user.type:admin,instructor');
        
        // Endpoints especializados de diálogos
        Route::post('/{dialogo}/activar', [DialogoController::class, 'activar'])->middleware('user.type:admin,instructor');
        Route::post('/{dialogo}/copiar', [DialogoController::class, 'copiar']);
        Route::get('/{dialogo}/estructura', [DialogoController::class, 'estructura']);
        Route::post('/{dialogo}/posiciones', [DialogoController::class, 'actualizarPosiciones']);
        Route::get('/{dialogo}/export', [App\Http\Controllers\DialogoImportController::class, 'exportar']);
        
        // Rutas de nodos de diálogo
        Route::post('/{dialogo}/nodos', [NodoDialogoController::class, 'store'])->middleware('user.type:admin,instructor');
    });

    // ========================================
    // RUTAS DE NODOS DE DIÁLOGO
    // ========================================
    Route::group(['prefix' => 'nodos'], function () {
        Route::put('/{nodoDialogo}', [NodoDialogoController::class, 'update'])->middleware('user.type:admin,instructor');
        Route::delete('/{nodoDialogo}', [NodoDialogoController::class, 'destroy'])->middleware('user.type:admin,instructor');
        Route::post('/{nodoDialogo}/marcar-inicial', [NodoDialogoController::class, 'marcarComoInicial'])->middleware('user.type:admin,instructor');
        Route::get('/{nodoDialogo}/respuestas', [NodoDialogoController::class, 'obtenerRespuestas']);
        Route::post('/{nodoDialogo}/respuestas', [NodoDialogoController::class, 'agregarRespuesta'])->middleware('user.type:admin,instructor');
    });

    // ========================================
    // RUTAS DE FLUJO DE DIÁLOGOS
    // ========================================
    Route::group(['prefix' => 'sesiones'], function () {
        // Endpoints de flujo de diálogos
        Route::post('/{sesionJuicio}/iniciar-dialogo', [DialogoFlujoController::class, 'iniciarDialogo'])->middleware('user.type:admin,instructor');
        Route::get('/{sesionJuicio}/dialogo-actual', [DialogoFlujoController::class, 'obtenerEstadoActual']);
        Route::get('/{sesionJuicio}/respuestas-disponibles/{usuario}', [DialogoFlujoController::class, 'obtenerRespuestasDisponibles']);
        Route::post('/{sesionJuicio}/procesar-decision', [DialogoFlujoController::class, 'procesarDecision']);
        Route::post('/{sesionJuicio}/avanzar-dialogo', [DialogoFlujoController::class, 'avanzarDialogo'])->middleware('user.type:admin,instructor');
        Route::post('/{sesionJuicio}/pausar-dialogo', [DialogoFlujoController::class, 'pausarDialogo'])->middleware('user.type:admin,instructor');
        Route::post('/{sesionJuicio}/finalizar-dialogo', [DialogoFlujoController::class, 'finalizarDialogo'])->middleware('user.type:admin,instructor');
        Route::get('/{sesionJuicio}/historial-decisiones', [DialogoFlujoController::class, 'obtenerHistorialDecisiones']);
    });
});

// ========================================
// RUTAS DE INTEGRACIÓN CON UNITY (SIN AUTENTICACIÓN GLOBAL)
// ========================================
Route::group(['prefix' => 'unity'], function () {
        
        // Endpoint de prueba simple
        Route::get('/test', function () {
            return response()->json([
                'success' => true,
                'message' => 'Unity API funcionando correctamente',
                'timestamp' => now()->toISOString()
            ]);
        });
        
        // Rutas de autenticación Unity (sin middleware de auth)
        Route::group(['prefix' => 'auth'], function () {
            Route::post('login', [UnityAuthController::class, 'login']);
            Route::get('status', [UnityAuthController::class, 'status']);
            
            Route::middleware('unity.auth')->group(function () {
                Route::post('refresh', [UnityAuthController::class, 'refresh']);
                Route::post('logout', [UnityAuthController::class, 'logout']);
                Route::get('me', [UnityAuthController::class, 'me']);
                
                // Nuevos endpoints para sesiones activas
                Route::get('session/active', [UnityAuthController::class, 'getActiveSession']);
                Route::get('session/{sessionId}/dialogue', [UnityAuthController::class, 'getSessionDialogue']);
            });
        });
        // Endpoints legacy (mantener compatibilidad)
        Route::get('/room-status/{roomId}', function ($roomId) {
            return response()->json([
                'success' => true,
                'message' => 'Estado del room de Unity - En desarrollo',
                'data' => ['room_id' => $roomId, 'status' => 'active']
            ]);
        });
        
        Route::post('/room-events', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Eventos del room de Unity - En desarrollo',
                'data' => $request->all()
            ]);
        });

        // Nuevos endpoints para diálogos Unity (requieren autenticación)
        Route::middleware('unity.auth')->group(function () {
            Route::group(['prefix' => 'sesion'], function () {
                Route::get('/{sesionJuicio}/dialogo-estado', [UnityDialogoController::class, 'obtenerEstadoDialogo']);
                Route::get('/{sesionJuicio}/respuestas-usuario/{usuario}', [UnityDialogoController::class, 'obtenerRespuestasUsuario']);
                Route::post('/{sesionJuicio}/enviar-decision', [UnityDialogoController::class, 'enviarDecision']);
                Route::post('/{sesionJuicio}/notificar-hablando', [UnityDialogoController::class, 'notificarHablando']);
                Route::get('/{sesionJuicio}/movimientos-personajes', [UnityDialogoController::class, 'obtenerMovimientosPersonajes']);
                
                // Rutas de comunicación en tiempo real
                Route::get('/{sesionJuicio}/events', [UnityRealtimeController::class, 'streamEvents']);
                Route::post('/{sesionJuicio}/broadcast', [UnityRealtimeController::class, 'broadcastEvent']);
                Route::get('/{sesionJuicio}/events/history', [UnityRealtimeController::class, 'getEventHistory']);
            });
            
            // Rutas de salas de Unity
            Route::group(['prefix' => 'rooms'], function () {
                Route::post('create', [UnityRoomController::class, 'createRoom']);
                Route::get('{roomId}/join', [UnityRoomController::class, 'joinRoom']);
                Route::post('{roomId}/leave', [UnityRoomController::class, 'leaveRoom']);
                Route::get('{roomId}/state', [UnityRoomController::class, 'getRoomState']);
                Route::post('{roomId}/sync-player', [UnityRoomController::class, 'syncPlayer']);
                Route::post('{roomId}/audio-state', [UnityRoomController::class, 'updateAudioState']);
                Route::get('{roomId}/events', [UnityRoomController::class, 'getRoomEvents']);
                Route::post('{roomId}/close', [UnityRoomController::class, 'closeRoom']);
            });
        });
    });

    // ========================================
    // RUTAS DE PERFIL DE USUARIO
    // ========================================
    Route::group(['prefix' => 'profile'], function () {
        Route::put('/', [ProfileController::class, 'update']);
        Route::get('/estadisticas', [ProfileController::class, 'estadisticas']);
        Route::get('/actividad', [ProfileController::class, 'actividad']);
        Route::get('/exportar-datos', [ProfileController::class, 'exportarDatos']);
        Route::post('/eliminar-cuenta', [ProfileController::class, 'eliminarCuenta']);
    });

    // ========================================
    // PANEL DE DIÁLOGOS (NUEVO SISTEMA)
    // ========================================
    Route::middleware('web.auth')->group(function () {
        Route::group(['prefix' => 'panel-dialogos'], function () {
        // Escenarios
        Route::get('/', [PanelDialogoController::class, 'index']);
        Route::post('/', [PanelDialogoController::class, 'store']);
        Route::get('/{escenario}', [PanelDialogoController::class, 'show']);
        Route::put('/{escenario}', [PanelDialogoController::class, 'update']);
        Route::delete('/{escenario}', [PanelDialogoController::class, 'destroy']);
        Route::post('/{escenario}/activar', [PanelDialogoController::class, 'activar']);
        Route::post('/{escenario}/copiar', [PanelDialogoController::class, 'copiar']);
        Route::get('/{escenario}/estructura', [PanelDialogoController::class, 'estructura']);
        
        // Roles
        Route::group(['prefix' => '{escenario}/roles'], function () {
            Route::get('/', [PanelDialogoController::class, 'roles']);
            Route::post('/', [PanelDialogoController::class, 'crearRol']);
            Route::put('/{rol}', [PanelDialogoController::class, 'actualizarRol']);
            Route::delete('/{rol}', [PanelDialogoController::class, 'eliminarRol']);
        });
        
        // Flujos
        Route::group(['prefix' => '{escenario}/flujos'], function () {
            Route::get('/', [PanelDialogoController::class, 'flujos']);
            Route::post('/', [PanelDialogoController::class, 'crearFlujo']);
            Route::put('/{flujo}', [PanelDialogoController::class, 'actualizarFlujo']);
            Route::delete('/{flujo}', [PanelDialogoController::class, 'eliminarFlujo']);
        });
        
        // Diálogos
        Route::group(['prefix' => '{escenario}/dialogos'], function () {
            Route::get('/', [PanelDialogoController::class, 'dialogos']);
            Route::post('/', [PanelDialogoController::class, 'crearDialogo']);
            Route::put('/{dialogo}', [PanelDialogoController::class, 'actualizarDialogo']);
            Route::delete('/{dialogo}', [PanelDialogoController::class, 'eliminarDialogo']);
            Route::post('/{dialogo}/duplicar', [PanelDialogoController::class, 'duplicarDialogo']);
        });
        
        // Opciones
        Route::group(['prefix' => '{escenario}/opciones'], function () {
            Route::post('/', [PanelDialogoController::class, 'crearOpcion']);
            Route::put('/{opcion}', [PanelDialogoController::class, 'actualizarOpcion']);
            Route::delete('/{opcion}', [PanelDialogoController::class, 'eliminarOpcion']);
        });
        
        // Conexiones
        Route::group(['prefix' => '{escenario}/conexiones'], function () {
            Route::get('/', [PanelDialogoController::class, 'conexiones']);
            Route::post('/', [PanelDialogoController::class, 'crearConexion']);
            Route::put('/{conexion}', [PanelDialogoController::class, 'actualizarConexion']);
            Route::delete('/{conexion}', [PanelDialogoController::class, 'eliminarConexion']);
        });
    });
    });