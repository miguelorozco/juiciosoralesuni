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
use App\Http\Controllers\NodoDialogoController;

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
    });

    // ========================================
    // RUTAS DE USUARIOS (ADMINISTRACIÓN)
    // ========================================
    Route::group(['prefix' => 'usuarios', 'middleware' => 'user.type:admin'], function () {
        Route::get('/', function () {
            return response()->json([
                'success' => true,
                'message' => 'Endpoint de usuarios - En desarrollo',
                'data' => []
            ]);
        });
        
        Route::get('/{id}', function ($id) {
            return response()->json([
                'success' => true,
                'message' => 'Endpoint de usuario específico - En desarrollo',
                'data' => ['id' => $id]
            ]);
        });
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
    Route::group(['prefix' => 'estadisticas', 'middleware' => 'user.type:admin,instructor'], function () {
        Route::get('/dashboard', function () {
            return response()->json([
                'success' => true,
                'message' => 'Dashboard de estadísticas - En desarrollo',
                'data' => [
                    'total_sesiones' => 0,
                    'sesiones_activas' => 0,
                    'total_usuarios' => 0,
                    'total_plantillas' => 0
                ]
            ]);
        });
        
        Route::get('/sesiones-por-mes', function () {
            return response()->json([
                'success' => true,
                'message' => 'Estadísticas de sesiones por mes - En desarrollo',
                'data' => []
            ]);
        });
    });

    // ========================================
    // RUTAS DE DIÁLOGOS RAMIFICADOS
    // ========================================
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

    // ========================================
    // RUTAS DE INTEGRACIÓN CON UNITY
    // ========================================
    Route::group(['prefix' => 'unity'], function () {
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

        // Nuevos endpoints para diálogos Unity
        Route::group(['prefix' => 'sesion'], function () {
            Route::get('/{sesionJuicio}/dialogo-estado', [UnityDialogoController::class, 'obtenerEstadoDialogo']);
            Route::get('/{sesionJuicio}/respuestas-usuario/{usuario}', [UnityDialogoController::class, 'obtenerRespuestasUsuario']);
            Route::post('/{sesionJuicio}/enviar-decision', [UnityDialogoController::class, 'enviarDecision']);
            Route::post('/{sesionJuicio}/notificar-hablando', [UnityDialogoController::class, 'notificarHablando']);
            Route::get('/{sesionJuicio}/movimientos-personajes', [UnityDialogoController::class, 'obtenerMovimientosPersonajes']);
        });
    });
});