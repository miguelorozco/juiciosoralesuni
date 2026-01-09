<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServeUnityAssets
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Solo procesar archivos Unity
        $path = $request->path();
        
        if (str_starts_with($path, 'unity-build/')) {
            // Detectar archivos comprimidos con Brotli (.br)
            if (preg_match('/\.(js|data|wasm)\.br$/', $path)) {
                // Establecer Content-Encoding para archivos Brotli
                $response->headers->set('Content-Encoding', 'br');
                
                // Establecer Content-Type apropiado
                if (str_ends_with($path, '.js.br')) {
                    $response->headers->set('Content-Type', 'application/javascript');
                } elseif (str_ends_with($path, '.wasm.br')) {
                    $response->headers->set('Content-Type', 'application/wasm');
                } elseif (str_ends_with($path, '.data.br')) {
                    $response->headers->set('Content-Type', 'application/octet-stream');
                }
                
                // Headers de cache
                $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            }
            
            // Headers CORS para Unity
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Unity-Version, X-Unity-Platform');
        }
        
        return $response;
    }
}

