<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    // Reclamar un rol
    public function claim(Request $request): JsonResponse
    {
        $role = $request->input('role');
        $userId = Auth::id() ?? $request->input('user_id');
        if (!$role || !$userId) {
            return response()->json(['success' => false, 'message' => 'Faltan datos'], 400);
        }
        $key = "role:$role";
        if (Cache::has($key)) {
            return response()->json(['success' => false, 'message' => 'Rol ocupado'], 409);
        }
        Cache::put($key, $userId, 3600);
        Log::info("Rol $role reclamado por usuario $userId");
        return response()->json(['success' => true, 'message' => 'Rol reclamado']);
    }

    // Liberar un rol
    public function release(Request $request): JsonResponse
    {
        $role = $request->input('role');
        $userId = Auth::id() ?? $request->input('user_id');
        $key = "role:$role";
        if (Cache::get($key) == $userId) {
            Cache::forget($key);
            Log::info("Rol $role liberado por usuario $userId");
            return response()->json(['success' => true, 'message' => 'Rol liberado']);
        }
        return response()->json(['success' => false, 'message' => 'No autorizado o rol no ocupado'], 403);
    }

    // Consultar estado de roles
    public function status(): JsonResponse
    {
        $roles = \App\Models\RolDisponible::where('activo', true)->orderBy('orden')->get();
        $status = [];
        foreach ($roles as $rol) {
            $key = "role:" . $rol->nombre;
            $status[$rol->nombre] = [
                'ocupado_por' => Cache::get($key),
                'color' => $rol->color,
                'descripcion' => $rol->descripcion,
                'orden' => $rol->orden
            ];
        }
        return response()->json(['success' => true, 'roles' => $status]);
    }
}
