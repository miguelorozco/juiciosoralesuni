<?php

namespace App\Http\Controllers;

use App\Models\PanelDialogoEscenario;
use App\Models\PanelDialogoRol;
use App\Models\PanelDialogoFlujo;
use App\Models\PanelDialogoDialogo;
use App\Models\PanelDialogoOpcion;
use App\Models\PanelDialogoConexion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PanelDialogoController extends Controller
{
    /**
     * Listar escenarios de diálogo
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $query = PanelDialogoEscenario::with(['creador', 'roles'])
                ->disponiblesParaUsuario($user);

            // Filtros
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nombre', 'like', '%' . $buscar . '%')
                      ->orWhere('descripcion', 'like', '%' . $buscar . '%');
                });
            }

            if ($request->has('tipo')) {
                $query->porTipo($request->tipo);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'updated_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 20);
            $escenarios = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $escenarios,
                'message' => 'Escenarios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener escenarios', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escenarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo escenario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
                'tipo' => 'required|in:civil,penal,laboral,administrativo',
                'publico' => 'boolean',
                'roles' => 'required|array|min:1',
                'roles.*.rol_id' => 'required|exists:roles_disponibles,id',
                'roles.*.requerido' => 'boolean',
            ]);

            DB::beginTransaction();

            // Crear escenario
            $escenario = PanelDialogoEscenario::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'creado_por' => auth()->id(),
                'estado' => 'borrador',
                'configuracion' => [
                    'tipo' => $validated['tipo'],
                    'publico' => $validated['publico'] ?? false,
                ],
            ]);

            // Crear roles del escenario basados en los roles disponibles seleccionados
            foreach ($validated['roles'] as $rolData) {
                $rolDisponible = \App\Models\RolDisponible::find($rolData['rol_id']);
                
                $rol = PanelDialogoRol::create([
                    'escenario_id' => $escenario->id,
                    'nombre' => $rolDisponible->nombre,
                    'descripcion' => $rolDisponible->descripcion,
                    'es_principal' => $rolData['requerido'] ?? false,
                    'configuracion' => [
                        'rol_disponible_id' => $rolDisponible->id,
                        'color' => $rolDisponible->color,
                        'icono' => $rolDisponible->icono,
                        'requerido' => $rolData['requerido'] ?? false,
                    ],
                ]);

                // Crear flujo principal para el rol
                $flujo = PanelDialogoFlujo::create([
                    'escenario_id' => $escenario->id,
                    'rol_id' => $rol->id,
                    'nombre' => 'Flujo Principal de ' . $rol->nombre,
                    'descripcion' => 'Flujo principal para el rol ' . $rol->nombre,
                    'configuracion' => [
                        'orden' => 0,
                        'activo' => true
                    ],
                ]);

                // Crear diálogo inicial automático
                $dialogoInicial = PanelDialogoDialogo::create([
                    'flujo_id' => $flujo->id,
                    'titulo' => 'Inicio - ' . $rol->nombre,
                    'contenido' => 'Este es el diálogo inicial para ' . $rol->nombre,
                    'tipo' => 'automatico',
                    'es_inicial' => true,
                    'es_final' => false,
                    'orden' => 0,
                    'metadata' => ['x' => 100, 'y' => 100]
                ]);

                // Crear diálogo final automático
                $dialogoFinal = PanelDialogoDialogo::create([
                    'flujo_id' => $flujo->id,
                    'titulo' => 'Final - ' . $rol->nombre,
                    'contenido' => 'Este es el diálogo final para ' . $rol->nombre,
                    'tipo' => 'final',
                    'es_inicial' => false,
                    'es_final' => true,
                    'orden' => 1,
                    'metadata' => ['x' => 400, 'y' => 100]
                ]);
            }

            DB::commit();

            $escenario->load(['creador', 'roles.flujos.dialogos']);

            return response()->json([
                'success' => true,
                'data' => $escenario,
                'message' => 'Escenario creado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear escenario', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el escenario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar escenario específico
     */
    public function show(PanelDialogoEscenario $escenario): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$escenario->puedeSerUsadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este escenario'
                ], 403);
            }

            $estructuraCompleta = $escenario->obtenerEstructuraCompleta();

            return response()->json([
                'success' => true,
                'data' => $estructuraCompleta,
                'message' => 'Escenario obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener escenario', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el escenario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar escenario
     */
    public function update(Request $request, PanelDialogoEscenario $escenario): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$escenario->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este escenario'
                ], 403);
            }

            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:200',
                'descripcion' => 'nullable|string',
                'tipo' => 'sometimes|required|in:civil,penal,laboral,administrativo',
                'publico' => 'boolean',
                'configuracion' => 'nullable|array',
                'estado' => 'sometimes|required|in:borrador,activo,archivado'
            ]);

            $escenario->update($validated);
            $escenario->load(['creador', 'roles.flujos.dialogos']);

            return response()->json([
                'success' => true,
                'data' => $escenario,
                'message' => 'Escenario actualizado exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar escenario', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el escenario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar escenario
     */
    public function destroy(PanelDialogoEscenario $escenario): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$escenario->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este escenario'
                ], 403);
            }

            // Verificar si el escenario está siendo usado
            if ($escenario->sesiones()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el escenario porque está siendo usado en sesiones'
                ], 409);
            }

            $escenario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Escenario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar escenario', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el escenario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar escenario
     */
    public function activar(PanelDialogoEscenario $escenario): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$escenario->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para activar este escenario'
                ], 403);
            }

            // Verificar que tenga al menos un rol con flujo
            if ($escenario->roles()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El escenario debe tener al menos un rol para ser activado'
                ], 400);
            }

            $escenario->activar();

            return response()->json([
                'success' => true,
                'data' => $escenario,
                'message' => 'Escenario activado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al activar escenario', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al activar el escenario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copiar escenario
     */
    public function copiar(Request $request, PanelDialogoEscenario $escenario): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$escenario->puedeSerUsadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este escenario'
                ], 403);
            }

            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
            ]);

            $nuevoEscenario = $escenario->crearCopia($validated['nombre'], $user->id);
            
            if (isset($validated['descripcion'])) {
                $nuevoEscenario->update(['descripcion' => $validated['descripcion']]);
            }

            $nuevoEscenario->load(['creador', 'roles.flujos.dialogos']);

            return response()->json([
                'success' => true,
                'data' => $nuevoEscenario,
                'message' => 'Escenario copiado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al copiar escenario', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al copiar el escenario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estructura completa del escenario para el editor
     */
    public function estructura(PanelDialogoEscenario $escenario): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$escenario->puedeSerUsadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este escenario'
                ], 403);
            }

            $estructura = $escenario->obtenerEstructuraCompleta();

            return response()->json([
                'success' => true,
                'data' => $estructura,
                'message' => 'Estructura del escenario obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estructura', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la estructura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista web del índice
     */
    public function indexWeb()
    {
        $user = auth()->user();
        
        $escenarios = PanelDialogoEscenario::with(['creador', 'roles'])
            ->disponiblesParaUsuario($user)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('dialogos.panel-index', compact('escenarios'));
    }

    /**
     * Vista web del editor
     */
    public function editor(PanelDialogoEscenario $escenario)
    {
        // Cargar el escenario con todos sus datos relacionados
        $escenario->load([
            'roles.flujos.dialogos.opciones',
            'roles' => function($query) {
                $query->orderBy('orden');
            }
        ]);
        
        return view('dialogos.editor-flujo-por-rol', compact('escenario'));
    }

    /**
     * Vista web de creación
     */
    public function create()
    {
        $rolesDisponibles = \App\Models\RolDisponible::activos()->ordenados()->get();
        return view('dialogos.crear-escenario', compact('rolesDisponibles'));
    }
}