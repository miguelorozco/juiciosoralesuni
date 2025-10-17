<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ConfiguracionSistemaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ConfiguracionSistema::with('actualizadoPor');

            // Filtros opcionales
            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('clave', 'like', '%' . $buscar . '%')
                      ->orWhere('descripcion', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'clave');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 50);
            $configuraciones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Configuraciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las configuraciones: ' . $e->getMessage()
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
                'clave' => 'required|string|max:100|unique:configuraciones_sistema,clave',
                'valor' => 'required|string',
                'descripcion' => 'nullable|string',
                'tipo' => 'required|in:string,number,boolean,json'
            ]);

            $validated['actualizado_por'] = auth()->id();
            $validated['fecha_actualizacion'] = now();

            $configuracion = ConfiguracionSistema::create($validated);
            $configuracion->load('actualizadoPor');

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración creada exitosamente'
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
                'message' => 'Error al crear la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ConfiguracionSistema $configuracionSistema): JsonResponse
    {
        try {
            $configuracionSistema->load('actualizadoPor');

            return response()->json([
                'success' => true,
                'data' => $configuracionSistema,
                'message' => 'Configuración obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ConfiguracionSistema $configuracionSistema): JsonResponse
    {
        try {
            $validated = $request->validate([
                'valor' => 'sometimes|required|string',
                'descripcion' => 'nullable|string',
                'tipo' => 'sometimes|required|in:string,number,boolean,json'
            ]);

            $validated['actualizado_por'] = auth()->id();
            $validated['fecha_actualizacion'] = now();

            $configuracionSistema->update($validated);
            $configuracionSistema->load('actualizadoPor');

            return response()->json([
                'success' => true,
                'data' => $configuracionSistema,
                'message' => 'Configuración actualizada exitosamente'
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
                'message' => 'Error al actualizar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConfiguracionSistema $configuracionSistema): JsonResponse
    {
        try {
            $configuracionSistema->delete();

            return response()->json([
                'success' => true,
                'message' => 'Configuración eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración por clave
     */
    public function obtener(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'clave' => 'required|string'
            ]);

            $configuracion = ConfiguracionSistema::where('clave', $validated['clave'])->first();

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'clave' => $configuracion->clave,
                    'valor' => $configuracion->valor_formateado,
                    'tipo' => $configuracion->tipo,
                    'descripcion' => $configuracion->descripcion
                ],
                'message' => 'Configuración obtenida exitosamente'
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
                'message' => 'Error al obtener la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Establecer configuración
     */
    public function establecer(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'clave' => 'required|string|max:100',
                'valor' => 'required|string',
                'descripcion' => 'nullable|string',
                'tipo' => 'required|in:string,number,boolean,json'
            ]);

            $configuracion = ConfiguracionSistema::establecer(
                $validated['clave'],
                $validated['valor'],
                $validated['descripcion'] ?? null,
                $validated['tipo'],
                auth()->id()
            );

            $configuracion->load('actualizadoPor');

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración establecida exitosamente'
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
                'message' => 'Error al establecer la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las configuraciones como array clave-valor
     */
    public function todas(): JsonResponse
    {
        try {
            $configuraciones = ConfiguracionSistema::obtenerTodas();

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Todas las configuraciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener todas las configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuraciones por tipo
     */
    public function porTipo(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo' => 'required|in:string,number,boolean,json'
            ]);

            $configuraciones = ConfiguracionSistema::obtenerPorTipo($validated['tipo']);

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Configuraciones por tipo obtenidas exitosamente'
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
                'message' => 'Error al obtener las configuraciones por tipo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function actualizarMultiples(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'configuraciones' => 'required|array',
                'configuraciones.*.clave' => 'required|string',
                'configuraciones.*.valor' => 'required|string',
                'configuraciones.*.tipo' => 'required|in:string,number,boolean,json',
                'configuraciones.*.descripcion' => 'nullable|string'
            ]);

            $actualizadas = [];

            foreach ($validated['configuraciones'] as $configData) {
                $configuracion = ConfiguracionSistema::establecer(
                    $configData['clave'],
                    $configData['valor'],
                    $configData['descripcion'] ?? null,
                    $configData['tipo'],
                    auth()->id()
                );
                $actualizadas[] = $configuracion;
            }

            return response()->json([
                'success' => true,
                'data' => $actualizadas,
                'message' => 'Configuraciones actualizadas exitosamente'
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
                'message' => 'Error al actualizar las configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuraciones del sistema
     */
    public function sistema(): JsonResponse
    {
        try {
            $configuraciones = ConfiguracionSistema::whereIn('clave', [
                'sistema_nombre',
                'sistema_version',
                'sistema_descripcion',
                'max_participantes_por_sesion',
                'tiempo_maximo_sesion',
                'habilitar_grabacion',
                'habilitar_transcripcion',
                'servidor_peerjs',
                'servidor_photon',
                'modo_desarrollo'
            ])->get()->pluck('valor_formateado', 'clave');

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Configuraciones del sistema obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las configuraciones del sistema: ' . $e->getMessage()
            ], 500);
        }
    }
}