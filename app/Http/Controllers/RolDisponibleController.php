<?php

namespace App\Http\Controllers;

use App\Models\RolDisponible;
use App\Http\Requests\StoreRolDisponibleRequest;
use App\Http\Requests\UpdateRolDisponibleRequest;
use App\Http\Requests\ReordenarRolesRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RolDisponibleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Listar roles",
     *     description="Obtiene una lista paginada de todos los roles disponibles",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="activo",
     *         in="query",
     *         description="Filtrar por estado activo",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="tipo",
     *         in="query",
     *         description="Filtrar por tipo de rol",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo para ordenar",
     *         required=false,
     *         @OA\Schema(type="string", default="orden")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Orden (asc/desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles obtenidos exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = RolDisponible::query();

            // Filtros opcionales
            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            if ($request->has('tipo')) {
                $query->where('nombre', 'like', '%' . $request->tipo . '%');
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'orden');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 20);
            $roles = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Roles obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Crear rol",
     *     description="Crea un nuevo rol en el sistema",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreRolRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rol creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/RolDisponible"),
     *             @OA\Property(property="message", type="string", example="Rol creado exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos suficientes",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de validación incorrectos",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorValidation")
     *     )
     * )
     * 
     * Store a newly created resource in storage.
     */
    public function store(StoreRolDisponibleRequest $request): JsonResponse
    {
        try {
            $rol = RolDisponible::create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Rol creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RolDisponible $rolDisponible): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $rolDisponible,
                'message' => 'Rol obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRolDisponibleRequest $request, RolDisponible $rolDisponible): JsonResponse
    {
        try {
            $rolDisponible->update($request->validated());

            return response()->json([
                'success' => true,
                'data' => $rolDisponible->fresh(),
                'message' => 'Rol actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RolDisponible $rolDisponible): JsonResponse
    {
        try {
            // Verificar si el rol está siendo usado
            if ($rolDisponible->asignacionesPlantillas()->count() > 0 || 
                $rolDisponible->asignacionesRoles()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el rol porque está siendo usado en plantillas o sesiones'
                ], 409);
            }

            $rolDisponible->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener roles activos
     */
    public function activos(): JsonResponse
    {
        try {
            $roles = RolDisponible::activos()->ordenados()->get();

            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Roles activos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los roles activos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleActivo(RolDisponible $rolDisponible): JsonResponse
    {
        try {
            $rolDisponible->update(['activo' => !$rolDisponible->activo]);

            return response()->json([
                'success' => true,
                'data' => $rolDisponible->fresh(),
                'message' => 'Estado del rol actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reordenar roles
     */
    public function reordenar(ReordenarRolesRequest $request): JsonResponse
    {
        try {
            foreach ($request->validated()['roles'] as $rolData) {
                RolDisponible::where('id', $rolData['id'])
                    ->update(['orden' => $rolData['orden']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Roles reordenados exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar los roles: ' . $e->getMessage()
            ], 500);
        }
    }
}