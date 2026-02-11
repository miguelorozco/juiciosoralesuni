<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\DialogoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PanelDialogoController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Página de bienvenida
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Ruta de prueba
Route::get('/test', function () {
    return view('test');
})->name('test');

// Ruta de prueba de login
Route::get('/test-login', function () {
    return view('test-login');
})->name('test-login');

// Rutas de autenticación
Route::get('/login', function () {
    return view('auth.login', [
        'title' => 'Iniciar Sesión',
        'subtitle' => 'Accede al simulador de juicios orales'
    ]);
})->name('login');

Route::get('/register', function () {
    return view('auth.login', [
        'title' => 'Registrarse',
        'subtitle' => 'Crea tu cuenta en el simulador'
    ]);
})->name('register');

Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.web');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Página de información sobre la migración (pública)
Route::get('/dialogos/migration-info', function() {
    return view('dialogos.migration-info');
})->name('dialogos.migration-info');

// Rutas protegidas
Route::middleware(['web.auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Sesiones
    Route::get('/sesiones', [SesionController::class, 'index'])->name('sesiones.index');
    Route::get('/sesiones/create', [SesionController::class, 'create'])->name('sesiones.create');
    Route::post('/sesiones', [SesionController::class, 'store'])->name('sesiones.store');
    Route::get('/sesiones/{sesion}', [SesionController::class, 'show'])->name('sesiones.show');
    Route::get('/sesiones/{sesion}/edit', [SesionController::class, 'edit'])->name('sesiones.edit');
    Route::put('/sesiones/{sesion}', [SesionController::class, 'update'])->name('sesiones.update');
    Route::delete('/sesiones/{sesion}', [SesionController::class, 'destroy'])->name('sesiones.destroy');
    Route::post('/sesiones/{sesion}/iniciar', [SesionController::class, 'iniciar'])->name('sesiones.iniciar');
    Route::post('/sesiones/{sesion}/finalizar', [SesionController::class, 'finalizar'])->name('sesiones.finalizar');
    
    // API endpoints para sesiones
    Route::get('/api/sesiones/{sesion}/usuarios-asignados', [SesionController::class, 'getUsuariosAsignados'])->name('sesiones.usuarios-asignados');
        Route::get('/api/sesiones/{sesion}/roles-disponibles', [SesionController::class, 'getRolesDisponibles'])->name('sesiones.roles-disponibles');
    
    // Diálogos - Redirigir al sistema V2
    Route::get('/dialogos', function() {
        return redirect()->route('dialogos-v2.index');
    })->name('dialogos.index');
    
    Route::get('/dialogos/create', function() {
        return redirect()->route('dialogos-v2.create');
    })->name('dialogos.create');
    
    // Diálogos (Sistema Legacy)
    Route::get('/dialogos-legacy', [DialogoController::class, 'indexWeb'])->name('dialogos-legacy.index');
    Route::get('/dialogos-legacy/create', function() { return view('dialogos.editor-mejorado'); })->name('dialogos-legacy.create');
    Route::get('/dialogos-legacy/import', function() { return view('dialogos.import'); })->name('dialogos-legacy.import');
    Route::get('/dialogos-legacy/{dialogo}', [DialogoController::class, 'showWeb'])->name('dialogos-legacy.show');
    Route::get('/dialogos-legacy/{dialogo}/edit', function($id) { 
        return view('dialogos.editor-mejorado', ['dialogo' => \App\Models\Dialogo::findOrFail($id)]); 
    })->name('dialogos-legacy.edit');
    
    // Panel de Diálogos (Nuevo Sistema)
    Route::get('/panel-dialogos', [PanelDialogoController::class, 'indexWeb'])->name('panel-dialogos.index');
    Route::get('/panel-dialogos/create', [PanelDialogoController::class, 'create'])->name('panel-dialogos.create');
    
    // Editor de Diálogos v2
    Route::get('/dialogos-v2', [App\Http\Controllers\DialogoV2EditorController::class, 'index'])->name('dialogos-v2.index');
    Route::get('/dialogos-v2/create', [App\Http\Controllers\DialogoV2EditorController::class, 'create'])->name('dialogos-v2.create');
    Route::get('/dialogos-v2/{dialogo}/editor', [App\Http\Controllers\DialogoV2EditorController::class, 'show'])->name('dialogos-v2.editor');
    Route::get('/panel-dialogos/{escenario}', [PanelDialogoController::class, 'show'])->name('panel-dialogos.show');
    Route::get('/panel-dialogos/{escenario}/editor', [PanelDialogoController::class, 'editor'])->name('panel-dialogos.editor');
    Route::get('/panel-dialogos/{escenario}/edit', [PanelDialogoController::class, 'editor'])->name('panel-dialogos.edit');
    
    // Roles
    Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RolController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
    Route::get('/roles/{rol}', [RolController::class, 'show'])->name('roles.show');
    Route::get('/roles/{rol}/edit', [RolController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{rol}', [RolController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{rol}', [RolController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/{rol}/toggle-activo', [RolController::class, 'toggleActivo'])->name('roles.toggle-activo');
    Route::post('/roles/reordenar', [RolController::class, 'reordenar'])->name('roles.reordenar');
    Route::get('/api/roles/activos', [RolController::class, 'activos'])->name('roles.activos');
    
    // Estadísticas
    Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas');
    
    // Configuración
    Route::get('/configuracion', function () {
        return view('configuracion.index');
    })->name('configuracion');
    
    // Perfil
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    
    // Configuración
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings');
});

// Rutas de entrada a Unity (públicas)
Route::get('/unity-entry', [App\Http\Controllers\UnityEntryController::class, 'unityEntryPage'])->name('unity.entry');
Route::get('/api/unity-entry-info', [App\Http\Controllers\UnityEntryController::class, 'getUnityEntryInfo'])->name('unity.entry-info');

// ========================================
// LIVEKIT - Sala de prueba (pública)
// ========================================
Route::get('/livekit-test', function () {
    return view('livekit.test-room');
})->name('livekit.test');

// Ruta para servir archivos Unity con headers correctos (PÚBLICA - sin middleware)
// Los archivos están en storage/unity-build para evitar que php artisan serve los sirva directamente
Route::get('/unity-build/{path}', function ($path) {
    \Log::info('Unity asset requested: ' . $path);
    
    // Los archivos están en storage (fuera de public para evitar servido directo)
    // Soportar un modo temporal para servir los archivos sin compresión (.br)
    // añadiendo ?raw=1 o ?no_compression=1 a la URL.
    $serveRaw = request()->query('raw') == '1' || request()->query('no_compression') == '1';
    $candidatePath = storage_path('unity-build/' . $path);

    if ($serveRaw && preg_match('/\.br$/', $path)) {
        $unbr = preg_replace('/\.br$/', '', $path);
        $candidateUnbr = storage_path('unity-build/' . $unbr);
        if (file_exists($candidateUnbr)) {
            \Log::info('Unity asset raw requested, serving uncompressed: ' . $unbr);
            $candidatePath = $candidateUnbr;
            // update $path so later content-type detection picks correct extension
            $path = $unbr;
        } else {
            \Log::info('Unity asset raw requested but uncompressed file not found: ' . $unbr);
        }
    }

    // If the request targets a .br file but the .br does not exist and an uncompressed
    // variant DOES exist, redirect the client to the uncompressed URL. We redirect
    // instead of serving the uncompressed bytes with the .br URL to avoid mismatched
    // Content-Encoding headers (the browser/loader expects a Brotli response for .br URLs).
    if (preg_match('/\.br$/', $path)) {
        $unbrFallback = preg_replace('/\.br$/', '', $path);
        $candidateUnbrFallback = storage_path('unity-build/' . $unbrFallback);
        if (file_exists($candidateUnbrFallback)) {
            \Log::info('Requested .br but uncompressed exists, redirecting to uncompressed: ' . $unbrFallback);
            $query = request()->getQueryString();
            $target = url('unity-build/' . $unbrFallback) . ($query ? ('?' . $query) : '');
            return redirect()->to($target);
        }
    }

    $filePath = $candidatePath;

    // Verificar seguridad - asegurar que el path está dentro de unity-build
    $realPath = realpath($filePath);
    $basePathStorage = realpath(storage_path('unity-build'));

    $isValid = false;
    if ($realPath && $basePathStorage && strpos($realPath, $basePathStorage) === 0) {
        $isValid = true;
    }

    // Si el archivo solicitado no existe, intentar servir la variante comprimida (.br)
    if (!$isValid || !file_exists($realPath)) {
        // Solo intentar fallback si la ruta solicitada no termina en .br
        if (!preg_match('/\.br$/', $path)) {
            $brCandidate = storage_path('unity-build/' . $path . '.br');
            $brReal = realpath($brCandidate);
            if ($brReal && $basePathStorage && strpos($brReal, $basePathStorage) === 0 && file_exists($brReal)) {
                \Log::info('Fallback: serving compressed .br for requested: ' . $path);
                $realPath = $brReal;
                $path = $path . '.br';
                $isValid = true;
            }
        }
    }

    if (!$isValid || !file_exists($realPath)) {
        // Fallback: StreamingAssets/unity-error-handling.json puede no existir en el build; devolver defaults
        if (preg_match('#^StreamingAssets/unity-error-handling\.json$#', $path)) {
            $default = [
                'errorHandling' => [
                    'suppressBlitterErrors' => true,
                    'suppressFormatExceptions' => true,
                    'suppressPhotonErrors' => true,
                    'suppressServerCertificateErrors' => true,
                    'logErrorsToConsole' => true,
                    'showErrorsToUser' => false,
                ],
                'initialization' => [
                    'preventMultipleInitialization' => true,
                    'cleanupOnRetry' => true,
                    'retryDelay' => 1000,
                    'maxRetries' => 3,
                ],
            ];
            return response()->json($default)
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', '*');
        }
        \Log::error('Unity asset not found: ' . $path . ' (realPath: ' . ($realPath ?: 'null') . ')');
        abort(404, 'File not found: ' . $path);
    }

    \Log::info('Serving Unity asset: ' . $realPath);
    
    // Allow a diagnostics mode to return metadata instead of the file
    if (request()->query('diag') == '1') {
        $meta = [
            'requested' => $path,
            'realPath' => $realPath,
            'size' => filesize($realPath),
            'exists' => file_exists($realPath),
            'contentEncoding' => null,
            'contentType' => null,
        ];
        if (preg_match('/\.(js|data|wasm)\.br$/', $path)) {
            $meta['contentEncoding'] = 'br';
            if (str_ends_with($path, '.js.br')) $meta['contentType'] = 'application/javascript';
            elseif (str_ends_with($path, '.wasm.br')) $meta['contentType'] = 'application/wasm';
            elseif (str_ends_with($path, '.data.br')) $meta['contentType'] = 'application/octet-stream';
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $mimeTypes = [
                'js' => 'application/javascript',
                'wasm' => 'application/wasm',
                'json' => 'application/json',
                'css' => 'text/css',
                'ico' => 'image/x-icon',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
            ];
            $meta['contentType'] = $mimeTypes[$ext] ?? 'application/octet-stream';
        }
        return response()->json($meta);
    }

    $response = response()->file($realPath);

    // Explicitly set Content-Length (response()->file usually handles it, but make explicit)
    try {
        $filesize = filesize($realPath);
        if ($filesize !== false) {
            $response->headers->set('Content-Length', (string) $filesize);
        }
    } catch (\Exception $e) {
        \Log::warning('Could not set Content-Length for Unity asset: ' . ($realPath ?: 'null'));
    }
    
    // Detectar archivos comprimidos con Brotli (.br)
    if (preg_match('/\.(js|data|wasm)\.br$/', $path)) {
        \Log::info('Setting Brotli headers for: ' . $path);
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
    } else {
        // Para archivos no comprimidos, establecer Content-Type apropiado
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mimeTypes = [
            'js' => 'application/javascript',
            'wasm' => 'application/wasm',
            'json' => 'application/json',
            'css' => 'text/css',
            'ico' => 'image/x-icon',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];
        if (isset($mimeTypes[$extension])) {
            $response->headers->set('Content-Type', $mimeTypes[$extension]);
        }
    }
    
    // Headers CORS para Unity
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Unity-Version, X-Unity-Platform');
    // Expose Content-Length so diagnostics can read it from client-side if needed
    $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, Content-Encoding');
    
    \Log::info('Unity asset served with headers: Content-Encoding=' . ($response->headers->get('Content-Encoding') ?: 'none') . ', Content-Type=' . $response->headers->get('Content-Type'));
    
    return $response;
})->where('path', '.*')->name('unity.assets');

// Rutas protegidas para generar enlaces de Unity
Route::middleware(['web.auth'])->group(function () {
    Route::post('/api/unity-entry/generate', [App\Http\Controllers\UnityEntryController::class, 'generateUnityEntryLink'])->name('unity.generate-link');
    
    // Ruta para el juego Unity - servir la vista Blade integrada
    // La vista `resources/views/unity/game.blade.php` carga los assets
    // mediante la ruta `unity.assets` y maneja mejor errores y logging.
    Route::get('/unity-game', function () {
        return view('unity.game');
    })->name('unity.game');
});