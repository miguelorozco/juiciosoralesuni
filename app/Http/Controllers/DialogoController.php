<?php

namespace App\Http\Controllers;

/**
 * @deprecated Este controlador usa modelos antiguos (Dialogo v1).
 * Se mantiene temporalmente para compatibilidad.
 * Usar DialogoV2Controller en su lugar cuando esté disponible.
 * 
 * TODO: Refactorizar para usar DialogoV2 o eliminar después de migración completa.
 */

use App\Models\DialogoV2 as Dialogo;
use App\Models\RolDisponible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class DialogoController extends Controller
{
    /**
     * @deprecated Usar DialogoV2Controller::index en su lugar
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $query = Dialogo::with(['creador', 'nodos.rol'])
                ->disponiblesParaUsuario($user);

            // Filtros opcionales
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nombre', 'like', '%' . $buscar . '%')
                      ->orWhere('descripcion', 'like', '%' . $buscar . '%');
                });
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('publico')) {
                $query->where('publico', $request->boolean('publico'));
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'updated_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 20);
            $dialogos = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $dialogos,
                'message' => 'Diálogos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los diálogos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::store en su lugar
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'plantilla_id' => 'nullable|exists:plantillas_sesiones,id',
                'publico' => 'boolean',
                'estado' => 'in:borrador,activo,archivado',
                'configuracion' => 'nullable|array',
            ]);

            $validated['creado_por'] = auth()->id();
            $validated['estado'] = $validated['estado'] ?? 'borrador';
            $validated['version'] = '1.0.0';

            $dialogo = Dialogo::create($validated);

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo creado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::show en su lugar
     */
    public function show(Dialogo $dialogo): JsonResponse
    {
        try {
            $dialogo->load(['creador', 'nodos.rol', 'nodos.respuestas']);

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::update en su lugar
     */
    public function update(Request $request, Dialogo $dialogo): JsonResponse
    {
        try {
            if (!$dialogo->puedeSerEditadoPor(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar este diálogo'
                ], 403);
            }

            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'nullable|string',
                'publico' => 'boolean',
                'estado' => 'in:borrador,activo,archivado',
                'configuracion' => 'nullable|array',
            ]);

            $dialogo->update($validated);

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo actualizado exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::destroy en su lugar
     */
    public function destroy(Dialogo $dialogo): JsonResponse
    {
        try {
            if (!$dialogo->puedeSerEditadoPor(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este diálogo'
                ], 403);
            }

            $dialogo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Diálogo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::activar en su lugar
     */
    public function activar(Dialogo $dialogo): JsonResponse
    {
        try {
            $dialogo->activar();

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo activado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::copiar en su lugar
     */
    public function copiar(Request $request, Dialogo $dialogo): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
            ]);

            $nuevoDialogo = $dialogo->crearCopia($request->nombre, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $nuevoDialogo,
                'message' => 'Diálogo copiado exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al copiar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::estructura en su lugar
     */
    public function estructura(Dialogo $dialogo): JsonResponse
    {
        try {
            $estructura = $dialogo->obtenerEstructuraGrafo();

            return response()->json([
                'success' => true,
                'data' => $estructura,
                'message' => 'Estructura del diálogo obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la estructura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @deprecated Usar DialogoV2Controller::actualizarPosiciones en su lugar
     */
    public function actualizarPosiciones(Request $request, Dialogo $dialogo): JsonResponse
    {
        try {
            $request->validate([
                'posiciones' => 'required|array',
                'posiciones.*.x' => 'required|integer',
                'posiciones.*.y' => 'required|integer',
            ]);

            $posiciones = [];
            foreach ($request->posiciones as $nodoId => $posicion) {
                $posiciones[$nodoId] = $posicion;
            }

            $dialogo->actualizarPosicionesNodos($posiciones);

            return response()->json([
                'success' => true,
                'message' => 'Posiciones actualizadas exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar las posiciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista web (deprecated)
     */
    public function indexWeb()
    {
        return view('dialogos.index');
    }

    /**
     * Vista web (deprecated)
     */
    public function showWeb($id)
    {
        return view('dialogos.editor-mejorado', ['dialogo' => Dialogo::findOrFail($id)]);
    }
}
