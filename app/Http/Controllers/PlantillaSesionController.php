<?php

namespace App\Http\Controllers;

use App\Models\PlantillaSesion;
use App\Models\RolDisponible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PlantillaSesionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PlantillaSesion::with(['creador', 'asignaciones.rol', 'asignaciones.usuario']);

            // Filtros opcionales
            if ($request->has('publica')) {
                $query->where('publica', $request->boolean('publica'));
            }

            if ($request->has('creado_por')) {
                $query->where('creado_por', $request->creado_por);
            }

            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nombre', 'like', '%' . $buscar . '%')
                      ->orWhere('descripcion', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'fecha_creacion');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 20);
            $plantillas = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $plantillas,
                'message' => 'Plantillas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las plantillas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
                'publica' => 'boolean',
                'configuracion' => 'nullable|array',
                'roles' => 'nullable|array',
                'roles.*.rol_id' => 'required|exists:roles_disponibles,id',
                'roles.*.usuario_id' => 'nullable|exists:users,id',
                'roles.*.orden' => 'nullable|integer|min:0'
            ]);

            $validated['creado_por'] = auth()->id();
            $validated['fecha_creacion'] = now();

            $plantilla = PlantillaSesion::create($validated);

            // Agregar roles si se proporcionaron
            if (isset($validated['roles'])) {
                foreach ($validated['roles'] as $rolData) {
                    $plantilla->agregarRol(
                        $rolData['rol_id'],
                        $rolData['usuario_id'] ?? null,
                        $rolData['orden'] ?? null
                    );
                }
            }

            $plantilla->load(['creador', 'asignaciones.rol', 'asignaciones.usuario']);

            return response()->json([
                'success' => true,
                'data' => $plantilla,
                'message' => 'Plantilla creada exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PlantillaSesion $plantillaSesion): JsonResponse
    {
        try {
            $plantillaSesion->load(['creador', 'asignaciones.rol', 'asignaciones.usuario', 'sesiones']);

            return response()->json([
                'success' => true,
                'data' => $plantillaSesion,
                'message' => 'Plantilla obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlantillaSesion $plantillaSesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:200',
                'descripcion' => 'nullable|string',
                'publica' => 'boolean',
                'configuracion' => 'nullable|array'
            ]);

            $plantillaSesion->update($validated);

            $plantillaSesion->load(['creador', 'asignaciones.rol', 'asignaciones.usuario']);

            return response()->json([
                'success' => true,
                'data' => $plantillaSesion,
                'message' => 'Plantilla actualizada exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlantillaSesion $plantillaSesion): JsonResponse
    {
        try {
            // Verificar si la plantilla está siendo usada
            if ($plantillaSesion->sesiones()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la plantilla porque está siendo usada en sesiones'
                ], 409);
            }

            $plantillaSesion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plantilla eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar rol a la plantilla
     */
    public function agregarRol(Request $request, PlantillaSesion $plantillaSesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rol_id' => 'required|exists:roles_disponibles,id',
                'usuario_id' => 'nullable|exists:users,id',
                'orden' => 'nullable|integer|min:0'
            ]);

            $asignacion = $plantillaSesion->agregarRol(
                $validated['rol_id'],
                $validated['usuario_id'] ?? null,
                $validated['orden'] ?? null
            );

            $asignacion->load(['rol', 'usuario']);

            return response()->json([
                'success' => true,
                'data' => $asignacion,
                'message' => 'Rol agregado a la plantilla exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover rol de la plantilla
     */
    public function removerRol(Request $request, PlantillaSesion $plantillaSesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rol_id' => 'required|exists:roles_disponibles,id'
            ]);

            $resultado = $plantillaSesion->removerRol($validated['rol_id']);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rol removido de la plantilla exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el rol en la plantilla'
                ], 404);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al remover el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear sesión desde plantilla
     */
    public function crearSesion(Request $request, PlantillaSesion $plantillaSesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
                'max_participantes' => 'nullable|integer|min:1|max:50',
                'configuracion' => 'nullable|array'
            ]);

            $validated['instructor_id'] = auth()->id();
            $validated['estado'] = 'programada';
            $validated['fecha_creacion'] = now();

            $sesion = $plantillaSesion->crearSesion($validated);

            return response()->json([
                'success' => true,
                'data' => $sesion,
                'message' => 'Sesión creada desde plantilla exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener plantillas públicas
     */
    public function publicas(): JsonResponse
    {
        try {
            $plantillas = PlantillaSesion::publicas()
                ->with(['creador', 'asignaciones.rol'])
                ->orderBy('fecha_creacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $plantillas,
                'message' => 'Plantillas públicas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las plantillas públicas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener plantillas del usuario actual
     */
    public function misPlantillas(): JsonResponse
    {
        try {
            $plantillas = PlantillaSesion::delUsuario(auth()->id())
                ->with(['creador', 'asignaciones.rol', 'asignaciones.usuario'])
                ->orderBy('fecha_creacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $plantillas,
                'message' => 'Mis plantillas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mis plantillas: ' . $e->getMessage()
            ], 500);
        }
    }
}