<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\DialogoController;
use App\Http\Controllers\RolController;

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

// Rutas protegidas
Route::middleware(['web.auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Sesiones
    Route::get('/sesiones', [SesionController::class, 'index'])->name('sesiones.index');
    Route::get('/sesiones/create', [SesionController::class, 'create'])->name('sesiones.create');
    Route::get('/sesiones/{sesion}', [SesionController::class, 'show'])->name('sesiones.show');
    Route::get('/sesiones/{sesion}/edit', [SesionController::class, 'edit'])->name('sesiones.edit');
    
    // Diálogos
    Route::get('/dialogos', [DialogoController::class, 'indexWeb'])->name('dialogos.index');
    Route::get('/dialogos/create', function() { return view('dialogos.editor-mejorado'); })->name('dialogos.create');
    Route::get('/dialogos/import', function() { return view('dialogos.import'); })->name('dialogos.import');
    Route::get('/dialogos/{dialogo}', [DialogoController::class, 'showWeb'])->name('dialogos.show');
    Route::get('/dialogos/{dialogo}/edit', function($id) { 
        return view('dialogos.editor-mejorado', ['dialogo' => \App\Models\Dialogo::findOrFail($id)]); 
    })->name('dialogos.edit');
    
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