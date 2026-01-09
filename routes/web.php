<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\DialogoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PanelDialogoController;

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
    Route::get('/estadisticas', function () {
        return view('estadisticas.index');
    })->name('estadisticas');
    
    // Configuración
    Route::get('/configuracion', function () {
        return view('configuracion.index');
    })->name('configuracion');
    
    // Perfil
    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');
    
    // Configuración
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings');
});

// Rutas de entrada a Unity (públicas)
Route::get('/unity-entry', [App\Http\Controllers\UnityEntryController::class, 'unityEntryPage'])->name('unity.entry');
Route::get('/api/unity-entry-info', [App\Http\Controllers\UnityEntryController::class, 'getUnityEntryInfo'])->name('unity.entry-info');

// Ruta para servir archivos Unity con headers correctos (PÚBLICA - sin middleware)
// Los archivos están en storage/unity-build para evitar que php artisan serve los sirva directamente
Route::get('/unity-build/{path}', function ($path) {
    \Log::info('Unity asset requested: ' . $path);
    
    // Los archivos están en storage (fuera de public para evitar servido directo)
    $filePath = storage_path('unity-build/' . $path);
    
    // Verificar seguridad - asegurar que el path está dentro de unity-build
    $realPath = realpath($filePath);
    $basePathStorage = realpath(storage_path('unity-build'));
    
    $isValid = false;
    if ($realPath && $basePathStorage && strpos($realPath, $basePathStorage) === 0) {
        $isValid = true;
    }
    
    if (!$isValid || !file_exists($realPath)) {
        \Log::error('Unity asset not found: ' . $path . ' (realPath: ' . ($realPath ?: 'null') . ')');
        abort(404, 'File not found: ' . $path);
    }
    
    \Log::info('Serving Unity asset: ' . $realPath);
    
    $response = response()->file($realPath);
    
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
        ];
        if (isset($mimeTypes[$extension])) {
            $response->headers->set('Content-Type', $mimeTypes[$extension]);
        }
    }
    
    // Headers CORS para Unity
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Unity-Version, X-Unity-Platform');
    
    \Log::info('Unity asset served with headers: Content-Encoding=' . ($response->headers->get('Content-Encoding') ?: 'none') . ', Content-Type=' . $response->headers->get('Content-Type'));
    
    return $response;
})->where('path', '.*')->name('unity.assets');

// Rutas protegidas para generar enlaces de Unity
Route::middleware(['web.auth'])->group(function () {
    Route::post('/api/unity-entry/generate', [App\Http\Controllers\UnityEntryController::class, 'generateUnityEntryLink'])->name('unity.generate-link');
    
    // Ruta para el juego Unity (requiere autenticación)
    Route::get('/unity-game', function () {
        return view('unity.game');
    })->name('unity.game');
});

// Rutas protegidas para generar enlaces de Unity
Route::middleware(['web.auth'])->group(function () {
    Route::post('/api/unity-entry/generate', [App\Http\Controllers\UnityEntryController::class, 'generateUnityEntryLink'])->name('unity.generate-link');
    
    // Ruta para el juego Unity (requiere autenticación)
    Route::get('/unity-game', function () {
        return view('unity.game');
    })->name('unity.game');
});