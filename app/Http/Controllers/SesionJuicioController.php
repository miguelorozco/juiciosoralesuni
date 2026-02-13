<?php

namespace App\Http\Controllers;

use App\Models\SesionJuicio;
use App\Models\PlantillaSesion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SesionJuicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SesionJuicio::withCount('asignaciones')
                ->with(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol']);

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('instructor_id')) {
                $query->where('instructor_id', $request->instructor_id);
            }

            if ($request->has('plantilla_id')) {
                $query->where('plantilla_id', $request->plantilla_id);
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
            $sesiones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $sesiones,
                'message' => 'Sesiones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones: ' . $e->getMessage()
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
                'plantilla_id' => 'nullable|exists:plantillas_sesiones,id',
                'max_participantes' => 'nullable|integer|min:1|max:50',
                'configuracion' => 'nullable|array',
                'participantes' => 'nullable|array',
                'participantes.*.usuario_id' => 'required|exists:users,id',
                'participantes.*.rol_id' => 'required|exists:roles_disponibles,id',
                'participantes.*.notas' => 'nullable|string'
            ]);

            $validated['instructor_id'] = auth()->id();
            $validated['estado'] = 'programada';
            $validated['fecha_creacion'] = now();

            $sesion = SesionJuicio::create($validated);

            // Agregar participantes si se proporcionaron
            if (isset($validated['participantes'])) {
                foreach ($validated['participantes'] as $participante) {
                    $sesion->agregarParticipante(
                        $participante['usuario_id'],
                        $participante['rol_id'],
                        auth()->id(),
                        $participante['notas'] ?? null
                    );
                }
            }

            $sesion->load(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol']);

            return response()->json([
                'success' => true,
                'data' => $sesion,
                'message' => 'Sesión creada exitosamente'
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
     * Display the specified resource.
     */
    public function show(SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            $sesionJuicio->load(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol', 'asignaciones.asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $sesionJuicio,
                'message' => 'Sesión obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:200',
                'descripcion' => 'nullable|string',
                'max_participantes' => 'nullable|integer|min:1|max:50',
                'configuracion' => 'nullable|array'
            ]);

            $sesionJuicio->update($validated);

            $sesionJuicio->load(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol']);

            return response()->json([
                'success' => true,
                'data' => $sesionJuicio,
                'message' => 'Sesión actualizada exitosamente'
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
                'message' => 'Error al actualizar la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            // Solo se pueden eliminar sesiones programadas
            if ($sesionJuicio->estado !== 'programada') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar sesiones programadas'
                ], 409);
            }

            $sesionJuicio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar sesión
     */
    public function iniciar(SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            if (!$sesionJuicio->puede_iniciar) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sesión no puede ser iniciada. Verifique que tenga participantes confirmados.'
                ], 409);
            }

            $resultado = $sesionJuicio->iniciar();

            if ($resultado) {
                $sesionJuicio->load(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol']);

                return response()->json([
                    'success' => true,
                    'data' => $sesionJuicio,
                    'message' => 'Sesión iniciada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo iniciar la sesión'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar sesión
     */
    public function finalizar(SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            if ($sesionJuicio->estado !== 'en_curso') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden finalizar sesiones en curso'
                ], 409);
            }

            $resultado = $sesionJuicio->finalizar();

            if ($resultado) {
                $sesionJuicio->load(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol']);

                return response()->json([
                    'success' => true,
                    'data' => $sesionJuicio,
                    'message' => 'Sesión finalizada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo finalizar la sesión'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar sesión
     */
    public function cancelar(SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            $resultado = $sesionJuicio->cancelar();

            if ($resultado) {
                $sesionJuicio->load(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol']);

                return response()->json([
                    'success' => true,
                    'data' => $sesionJuicio,
                    'message' => 'Sesión cancelada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cancelar la sesión'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar participante a la sesión
     */
    public function agregarParticipante(Request $request, SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|exists:users,id',
                'rol_id' => 'required|exists:roles_disponibles,id',
                'notas' => 'nullable|string'
            ]);

            // Verificar que el usuario no esté ya asignado
            if ($sesionJuicio->obtenerParticipantePorUsuario($validated['usuario_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario ya está asignado a esta sesión'
                ], 409);
            }

            // Verificar que el rol no esté ya asignado
            if ($sesionJuicio->obtenerParticipantePorRol($validated['rol_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol ya está asignado en esta sesión'
                ], 409);
            }

            $asignacion = $sesionJuicio->agregarParticipante(
                $validated['usuario_id'],
                $validated['rol_id'],
                auth()->id(),
                $validated['notas'] ?? null
            );

            $asignacion->load(['usuario', 'rol', 'asignadoPor']);

            return response()->json([
                'success' => true,
                'data' => $asignacion,
                'message' => 'Participante agregado exitosamente'
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
                'message' => 'Error al agregar el participante: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover participante de la sesión
     */
    public function removerParticipante(Request $request, SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|exists:users,id'
            ]);

            $resultado = $sesionJuicio->removerParticipante($validated['usuario_id']);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Participante removido exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el participante en la sesión'
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
                'message' => 'Error al remover el participante: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar Room ID para Unity
     */
    public function generarRoomId(SesionJuicio $sesionJuicio): JsonResponse
    {
        try {
            $roomId = $sesionJuicio->generarRoomId();

            return response()->json([
                'success' => true,
                'data' => ['unity_room_id' => $roomId],
                'message' => 'Room ID generado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el Room ID: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener sesiones activas
     */
    public function activas(): JsonResponse
    {
        try {
            $sesiones = SesionJuicio::activas()
                ->with(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol'])
                ->orderBy('fecha_creacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones,
                'message' => 'Sesiones activas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones activas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener sesiones del instructor actual
     */
    public function misSesiones(): JsonResponse
    {
        try {
            $sesiones = SesionJuicio::delInstructor(auth()->id())
                ->with(['instructor', 'plantilla', 'asignaciones.usuario', 'asignaciones.rol'])
                ->orderBy('fecha_creacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones,
                'message' => 'Mis sesiones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mis sesiones: ' . $e->getMessage()
            ], 500);
        }
    }
}