<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UsuarioController extends Controller
{
    /**
     * Obtener usuarios por tipo
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query();

            // Filtrar por tipo si se especifica
            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            // Filtrar solo usuarios activos
            $query->where('activo', true);

            // Ordenar por nombre
            $query->orderBy('name');

            $usuarios = $query->get();

            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => 'Usuarios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estudiantes disponibles
     */
    public function estudiantes(): JsonResponse
    {
        try {
            $estudiantes = User::where('tipo', 'alumno')
                ->where('activo', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $estudiantes,
                'message' => 'Estudiantes obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estudiantes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener instructores disponibles
     */
    public function instructores(): JsonResponse
    {
        try {
            $instructores = User::where('tipo', 'instructor')
                ->where('activo', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $instructores,
                'message' => 'Instructores obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener instructores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show(User $usuario): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario: ' . $e->getMessage()
            ], 500);
        }
    }
}