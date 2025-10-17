<?php

namespace App\Http\Controllers;

use App\Models\AsignacionRol;
use App\Models\SesionJuicio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AsignacionRolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AsignacionRol::with(['sesion', 'usuario', 'rol', 'asignadoPor']);

            // Filtros opcionales
            if ($request->has('sesion_id')) {
                $query->where('sesion_id', $request->sesion_id);
            }

            if ($request->has('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->has('rol_id')) {
                $query->where('rol_id', $request->rol_id);
            }

            if ($request->has('confirmado')) {
                $query->where('confirmado', $request->boolean('confirmado'));
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'fecha_asignacion');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 20);
            $asignaciones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $asignaciones,
                'message' => 'Asignaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asignaciones: ' . $e->getMessage()
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
                'sesion_id' => 'required|exists:sesiones_juicios,id',
                'usuario_id' => 'required|exists:users,id',
                'rol_id' => 'required|exists:roles_disponibles,id',
                'notas' => 'nullable|string'
            ]);

            $validated['asignado_por'] = auth()->id();
            $validated['fecha_asignacion'] = now();
            $validated['confirmado'] = false;

            // Verificar que el usuario no esté ya asignado a la sesión
            $asignacionExistente = AsignacionRol::where('sesion_id', $validated['sesion_id'])
                ->where('usuario_id', $validated['usuario_id'])
                ->first();

            if ($asignacionExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario ya está asignado a esta sesión'
                ], 409);
            }

            // Verificar que el rol no esté ya asignado en la sesión
            $rolExistente = AsignacionRol::where('sesion_id', $validated['sesion_id'])
                ->where('rol_id', $validated['rol_id'])
                ->first();

            if ($rolExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol ya está asignado en esta sesión'
                ], 409);
            }

            $asignacion = AsignacionRol::create($validated);
            $asignacion->load(['sesion', 'usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacion,
                'message' => 'Asignación creada exitosamente'
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
                'message' => 'Error al crear la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AsignacionRol $asignacionRol): JsonResponse
    {
        try {
            $asignacionRol->load(['sesion', 'usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacionRol,
                'message' => 'Asignación obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AsignacionRol $asignacionRol): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rol_id' => 'sometimes|required|exists:roles_disponibles,id',
                'notas' => 'nullable|string'
            ]);

            // Si se está cambiando el rol, verificar que no esté ya asignado
            if (isset($validated['rol_id']) && $validated['rol_id'] !== $asignacionRol->rol_id) {
                $rolExistente = AsignacionRol::where('sesion_id', $asignacionRol->sesion_id)
                    ->where('rol_id', $validated['rol_id'])
                    ->where('id', '!=', $asignacionRol->id)
                    ->first();

                if ($rolExistente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El rol ya está asignado en esta sesión'
                    ], 409);
                }

                $validated['confirmado'] = false; // Requiere nueva confirmación
            }

            $asignacionRol->update($validated);
            $asignacionRol->load(['sesion', 'usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacionRol,
                'message' => 'Asignación actualizada exitosamente'
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
                'message' => 'Error al actualizar la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AsignacionRol $asignacionRol): JsonResponse
    {
        try {
            // Verificar que la sesión no esté en curso
            if ($asignacionRol->sesion && $asignacionRol->sesion->estado === 'en_curso') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la asignación de una sesión en curso'
                ], 409);
            }

            $asignacionRol->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asignación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar asignación
     */
    public function confirmar(AsignacionRol $asignacionRol): JsonResponse
    {
        try {
            if (!$asignacionRol->puedeSerConfirmada()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La asignación no puede ser confirmada'
                ], 409);
            }

            $asignacionRol->confirmar();
            $asignacionRol->load(['sesion', 'usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacionRol,
                'message' => 'Asignación confirmada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desconfirmar asignación
     */
    public function desconfirmar(AsignacionRol $asignacionRol): JsonResponse
    {
        try {
            $asignacionRol->desconfirmar();
            $asignacionRol->load(['sesion', 'usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacionRol,
                'message' => 'Asignación desconfirmada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desconfirmar la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar rol de la asignación
     */
    public function cambiarRol(Request $request, AsignacionRol $asignacionRol): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rol_id' => 'required|exists:roles_disponibles,id'
            ]);

            // Verificar que el nuevo rol no esté ya asignado
            $rolExistente = AsignacionRol::where('sesion_id', $asignacionRol->sesion_id)
                ->where('rol_id', $validated['rol_id'])
                ->where('id', '!=', $asignacionRol->id)
                ->first();

            if ($rolExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol ya está asignado en esta sesión'
                ], 409);
            }

            $asignacionRol->cambiarRol($validated['rol_id'], auth()->id());
            $asignacionRol->load(['sesion', 'usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacionRol,
                'message' => 'Rol cambiado exitosamente'
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
                'message' => 'Error al cambiar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar notas de la asignación
     */
    public function actualizarNotas(Request $request, AsignacionRol $asignacionRol): JsonResponse
    {
        try {
            $validated = $request->validate([
                'notas' => 'nullable|string'
            ]);

            $asignacionRol->actualizarNotas($validated['notas']);
            $asignacionRol->load(['sesion', 'usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacionRol,
                'message' => 'Notas actualizadas exitosamente'
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
                'message' => 'Error al actualizar las notas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener asignaciones de una sesión
     */
    public function porSesion(SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            $asignaciones = AsignacionRol::deSesion($sesionJuicio->id)
                ->with(['usuario', 'rol', 'asignadoPor'])
                ->orderBy('fecha_asignacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $asignaciones,
                'message' => 'Asignaciones de la sesión obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asignaciones de la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener asignaciones del usuario actual
     */
    public function misAsignaciones(): JsonResponse
    {
        try {
            $asignaciones = AsignacionRol::delUsuario(auth()->id())
                ->with(['sesion', 'rol', 'asignadoPor'])
                ->orderBy('fecha_asignacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $asignaciones,
                'message' => 'Mis asignaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mis asignaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener asignaciones pendientes
     */
    public function pendientes(): JsonResponse
    {
        try {
            $asignaciones = AsignacionRol::pendientes()
                ->with(['sesion', 'usuario', 'rol', 'asignadoPor'])
                ->orderBy('fecha_asignacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $asignaciones,
                'message' => 'Asignaciones pendientes obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asignaciones pendientes: ' . $e->getMessage()
            ], 500);
        }
    }
}