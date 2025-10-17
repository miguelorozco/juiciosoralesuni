<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        Log::info('=== DASHBOARD CONTROLLER ===');
        
        $user = Auth::user();
        $isAuthenticated = Auth::check();
        
        Log::info('Auth::check(): ' . ($isAuthenticated ? 'true' : 'false'));
        Log::info('Auth::user(): ' . ($user ? json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tipo' => $user->tipo,
            'activo' => $user->activo
        ]) : 'null'));
        
        if (!$isAuthenticated) {
            Log::warning('Usuario no autenticado en dashboard, esto no debería pasar');
            return redirect()->route('login');
        }
        
        Log::info('Renderizando vista dashboard para usuario: ' . $user->email);
        
        return view('dashboard', [
            'user' => $user,
            'userType' => $user->tipo
        ]);
    }
}
