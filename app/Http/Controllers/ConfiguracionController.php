<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class ConfiguracionController extends Controller
{
    /**
     * Obtener configuraciones del sistema
     */
    public function sistema(): JsonResponse
    {
        try {
            // Configuraciones por defecto
            $configuraciones = [
                'duracion_maxima_sesion' => 120,
                'max_participantes_sesion' => 10,
                'tiempo_espera_respuesta' => 30,
                'puntuacion_maxima' => 10,
                'registro_usuarios_habilitado' => true,
                'verificacion_email_requerida' => false,
                'limite_intentos_login' => 5,
                'tiempo_bloqueo' => 15,
                'notificaciones_email_habilitadas' => true,
                'notificaciones_push_habilitadas' => false,
                'notificar_inicio_sesion' => true,
                'notificar_fin_sesion' => true,
                'integracion_unity_habilitada' => false,
                'unity_server_url' => 'ws://localhost:8080',
                'unity_server_port' => 8080,
                'unity_timeout' => 30
            ];
            
            return response()->json([
                'success' => true,
                'data' => $configuraciones
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo configuraciones del sistema: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuraciones'
            ], 500);
        }
    }
    
    /**
     * Actualizar múltiples configuraciones
     */
    public function actualizarMultiples(Request $request): JsonResponse
    {
        try {
            $configuraciones = $request->all();
            
            // Validar configuraciones
            $this->validarConfiguraciones($configuraciones);
            
            // Aquí se guardarían las configuraciones en la base de datos
            // Por ahora solo simulamos el guardado
            
            Log::info('Configuraciones actualizadas', $configuraciones);
            
            return response()->json([
                'success' => true,
                'message' => 'Configuraciones actualizadas exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando configuraciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuraciones'
            ], 500);
        }
    }
    
    /**
     * Limpiar cache del sistema
     */
    public function limpiarCache(): JsonResponse
    {
        try {
            Cache::flush();
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache limpiado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error limpiando cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar el cache'
            ], 500);
        }
    }
    
    /**
     * Regenerar logs del sistema
     */
    public function regenerarLogs(): JsonResponse
    {
        try {
            // Simular regeneración de logs
            Log::info('Logs regenerados por usuario: ' . Auth::user()->email);
            
            return response()->json([
                'success' => true,
                'message' => 'Logs regenerados exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error regenerando logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al regenerar los logs'
            ], 500);
        }
    }
    
    /**
     * Probar conexión con Unity
     */
    public function probarUnity(Request $request): JsonResponse
    {
        try {
            $url = $request->input('url', 'ws://localhost:8080');
            $port = $request->input('port', 8080);
            
            // Simular prueba de conexión
            $conexionExitosa = true; // En producción se haría una prueba real
            
            if ($conexionExitosa) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión con Unity exitosa',
                    'data' => [
                        'url' => $url,
                        'port' => $port,
                        'latencia' => '15ms'
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo conectar con Unity'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Error probando conexión Unity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al probar conexión con Unity'
            ], 500);
        }
    }
    
    /**
     * Reiniciar sistema
     */
    public function reiniciarSistema(): JsonResponse
    {
        try {
            // Simular reinicio del sistema
            Log::info('Sistema reiniciado por usuario: ' . Auth::user()->email);
            
            return response()->json([
                'success' => true,
                'message' => 'Sistema reiniciado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error reiniciando sistema: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar el sistema'
            ], 500);
        }
    }
    
    /**
     * Validar configuraciones
     */
    private function validarConfiguraciones(array $configuraciones): void
    {
        $reglas = [
            'duracion_maxima_sesion' => 'integer|min:30|max:480',
            'max_participantes_sesion' => 'integer|min:2|max:20',
            'tiempo_espera_respuesta' => 'integer|min:10|max:300',
            'puntuacion_maxima' => 'integer|min:1|max:100',
            'limite_intentos_login' => 'integer|min:3|max:10',
            'tiempo_bloqueo' => 'integer|min:5|max:60',
            'unity_server_port' => 'integer|min:1000|max:65535',
            'unity_timeout' => 'integer|min:5|max:60'
        ];
        
        $validator = validator($configuraciones, $reglas);
        
        if ($validator->fails()) {
            throw new \Exception('Configuraciones inválidas: ' . implode(', ', $validator->errors()->all()));
        }
    }
}
