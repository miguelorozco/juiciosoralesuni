<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Health check endpoint para verificar el estado de la API
     * 
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => []
        ];

        // Verificar conexión a base de datos
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'Conexión a base de datos exitosa',
                'connection' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => 'Error de conexión a base de datos: ' . $e->getMessage(),
            ];
        }

        // Verificar caché
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            $health['checks']['cache'] = [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'message' => $value === 'test' ? 'Sistema de caché funcionando' : 'Error en sistema de caché',
                'driver' => config('cache.default'),
            ];
            
            if ($value !== 'test') {
                $health['status'] = 'unhealthy';
            }
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Error en sistema de caché: ' . $e->getMessage(),
            ];
        }

        // Verificar sistema de archivos
        try {
            $storagePath = storage_path();
            $writable = is_writable($storagePath);
            
            $health['checks']['storage'] = [
                'status' => $writable ? 'healthy' : 'unhealthy',
                'message' => $writable ? 'Directorio de almacenamiento escribible' : 'Directorio de almacenamiento no escribible',
                'path' => $storagePath,
                'writable' => $writable,
            ];
            
            if (!$writable) {
                $health['status'] = 'unhealthy';
            }
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['storage'] = [
                'status' => 'unhealthy',
                'message' => 'Error verificando almacenamiento: ' . $e->getMessage(),
            ];
        }

        // Información del servidor
        $health['server'] = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'debug_mode' => config('app.debug'),
        ];

        // Estadísticas de la aplicación
        try {
            $health['statistics'] = [
                'total_users' => \App\Models\User::count(),
                'active_sessions' => \App\Models\SesionJuicio::where('estado', 'en_curso')->count(),
                'total_sessions' => \App\Models\SesionJuicio::count(),
                'total_dialogues' => \App\Models\DialogoV2::count(),
            ];
        } catch (\Exception $e) {
            $health['statistics'] = [
                'error' => 'No se pudieron obtener estadísticas: ' . $e->getMessage(),
            ];
        }

        // Determinar código de respuesta HTTP
        $httpStatus = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $httpStatus);
    }

    /**
     * Health check simple (solo verifica que el servidor responda)
     * 
     * @return JsonResponse
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'API funcionando correctamente',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Health check detallado con información del sistema
     * 
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $health = $this->check()->getData(true);
        
        // Agregar información adicional
        $health['system'] = [
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];

        $health['extensions'] = [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'curl' => extension_loaded('curl'),
            'zip' => extension_loaded('zip'),
            'json' => extension_loaded('json'),
            'openssl' => extension_loaded('openssl'),
        ];

        $httpStatus = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $httpStatus);
    }

    /**
     * Formatear bytes a formato legible
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

