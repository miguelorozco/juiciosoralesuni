<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RolDisponible;
use App\Http\Requests\StoreRolDisponibleRequest;
use App\Http\Requests\UpdateRolDisponibleRequest;
use App\Http\Requests\ReordenarRolesRequest;
use Illuminate\Http\JsonResponse;

class RolController extends Controller
{
    /**
     * Mostrar la vista principal de roles
     */
    public function index(Request $request)
    {
        $query = RolDisponible::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', '%' . $buscar . '%')
                  ->orWhere('descripcion', 'like', '%' . $buscar . '%');
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'orden');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 20);
        $roles = $query->paginate($perPage);

        return view('roles.index', compact('roles'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Mostrar un rol específico
     */
    public function show(RolDisponible $rol)
    {
        $rol->load(['asignacionesPlantillas.plantilla', 'asignacionesRoles.sesion']);
        
        return view('roles.show', compact('rol'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(RolDisponible $rol)
    {
        return view('roles.edit', compact('rol'));
    }

    /**
     * Crear nuevo rol
     */
    public function store(StoreRolDisponibleRequest $request)
    {
        try {
            $rol = RolDisponible::create($request->validated());

            return redirect()
                ->route('roles.show', $rol)
                ->with('success', 'Rol creado exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el rol: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar rol existente
     */
    public function update(UpdateRolDisponibleRequest $request, RolDisponible $rol)
    {
        try {
            $rol->update($request->validated());

            return redirect()
                ->route('roles.show', $rol)
                ->with('success', 'Rol actualizado exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el rol: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar rol
     */
    public function destroy(RolDisponible $rol)
    {
        try {
            // Verificar si el rol está siendo usado
            if ($rol->asignacionesPlantillas()->count() > 0 || 
                $rol->asignacionesRoles()->count() > 0) {
                return redirect()
                    ->back()
                    ->with('error', 'No se puede eliminar el rol porque está siendo usado en plantillas o sesiones');
            }

            $rol->delete();

            return redirect()
                ->route('roles.index')
                ->with('success', 'Rol eliminado exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleActivo(RolDisponible $rol)
    {
        try {
            $rol->update(['activo' => !$rol->activo]);

            $estado = $rol->activo ? 'activado' : 'desactivado';
            
            return redirect()
                ->back()
                ->with('success', "Rol {$estado} exitosamente");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al cambiar el estado del rol: ' . $e->getMessage());
        }
    }

    /**
     * Reordenar roles
     */
    public function reordenar(ReordenarRolesRequest $request)
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

    /**
     * Obtener roles activos para AJAX
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
}
