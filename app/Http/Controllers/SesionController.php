<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesionJuicio;
use App\Models\AsignacionRol;
use Illuminate\Http\JsonResponse;
use App\Services\ProcesamientoAutomaticoService;
use Illuminate\Support\Facades\Log;

class SesionController extends Controller
{
    /**
     * Mostrar la vista principal de sesiones
     */
    public function index(Request $request)
    {
        $query = SesionJuicio::with(['instructor', 'plantilla']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', '%' . $buscar . '%')
                  ->orWhere('descripcion', 'like', '%' . $buscar . '%')
                  ->orWhereHas('instructor', function($instructorQuery) use ($buscar) {
                      $instructorQuery->where('name', 'like', '%' . $buscar . '%');
                  });
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_inicio');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 20);
        $sesiones = $query->paginate($perPage);

        return view('sesiones.index', compact('sesiones'));
    }
    
    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $dialogos = \App\Models\Dialogo::where('estado', 'activo')
            ->where('publico', true)
            ->orderBy('nombre')
            ->get();
            
        return view('sesiones.create', compact('dialogos'));
    }
    
    /**
     * Crear nueva sesión
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:civil,penal,laboral,administrativo',
            'fecha_inicio' => 'required|date|after:now',
            'max_participantes' => 'nullable|integer|min:1|max:20',
            'dialogo_id' => 'required|exists:dialogos,id',
        ]);

        try {
            \DB::beginTransaction();

            // Crear la sesión
            $sesion = SesionJuicio::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'tipo' => $request->tipo,
                'fecha_inicio' => $request->fecha_inicio,
                'max_participantes' => $request->max_participantes ?? 10,
                'instructor_id' => auth()->id(),
                'estado' => 'programada',
            ]);

            // Obtener roles del diálogo seleccionado
            $dialogo = \App\Models\Dialogo::with('rolesActivos')->find($request->dialogo_id);
            $rolesDialogo = $dialogo->rolesActivos;

            // Procesar asignaciones automáticas de roles del diálogo
            $procesamientoService = new ProcesamientoAutomaticoService();
            $asignacionesRealizadas = $procesamientoService->procesarAsignacionesAutomaticasDialogo($sesion, $rolesDialogo);

            // Crear sesión de diálogo
            $this->crearSesionDialogo($sesion, $request->dialogo_id);

            \DB::commit();

            $mensaje = 'Sesión creada exitosamente. ';
            if (count($asignacionesRealizadas) > 0) {
                $mensaje .= 'Asignaciones automáticas realizadas: ' . count($asignacionesRealizadas) . ' roles asignados.';
            }

            return redirect()
                ->route('sesiones.show', $sesion)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Error creando sesión: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la sesión: ' . $e->getMessage());
        }
    }

    /**
     * Asignar rol a estudiante específico
     */
    private function asignarRolAEstudiante($sesion, $rol, $estudianteId)
    {
        // Verificar que el estudiante existe y es alumno
        $estudiante = \App\Models\User::where('id', $estudianteId)
            ->where('tipo', 'alumno')
            ->where('activo', true)
            ->first();

        if ($estudiante) {
            \App\Models\AsignacionRol::create([
                'sesion_id' => $sesion->id,
                'usuario_id' => $estudianteId,
                'rol_id' => $rol->id,
                'asignado_por' => auth()->id(),
                'confirmado' => false,
                'notas' => 'Asignado por instructor'
            ]);
        }
    }


    /**
     * Crear sesión de diálogo
     */
    private function crearSesionDialogo($sesion, $dialogoId)
    {
        $dialogo = \App\Models\Dialogo::findOrFail($dialogoId);
        
        \App\Models\SesionDialogo::create([
            'sesion_id' => $sesion->id,
            'dialogo_id' => $dialogoId,
            'estado' => 'programada',
            'nodo_actual_id' => $dialogo->nodo_inicial_id,
            'configuracion' => [
                'modo_automatico' => true,
                'tiempo_respuesta' => 30,
                'permite_pausa' => true
            ]
        ]);
    }
    
    /**
     * Mostrar una sesión específica
     */
    public function show(SesionJuicio $sesion)
    {
        $sesion->load(['instructor', 'asignaciones.usuario', 'asignaciones.rolDisponible']);
        return view('sesiones.show', compact('sesion'));
    }
    
    /**
     * Obtener usuarios asignados a una sesión
     */
    public function getUsuariosAsignados(SesionJuicio $sesion)
    {
        try {
            $asignaciones = AsignacionRol::with(['usuario', 'rolDisponible'])
                ->where('sesion_id', $sesion->id)
                ->get();

            $usuarios = $asignaciones->map(function ($asignacion) {
                return [
                    'usuario_id' => $asignacion->usuario_id,
                    'usuario' => [
                        'id' => $asignacion->usuario->id,
                        'name' => $asignacion->usuario->name,
                        'email' => $asignacion->usuario->email,
                    ],
                    'rol' => [
                        'id' => $asignacion->rolDisponible->id,
                        'nombre' => $asignacion->rolDisponible->nombre,
                        'descripcion' => $asignacion->rolDisponible->descripcion,
                        'color' => $asignacion->rolDisponible->color,
                        'icono' => $asignacion->rolDisponible->icono,
                    ],
                    'asignacion' => [
                        'id' => $asignacion->id,
                        'confirmado' => $asignacion->confirmado,
                        'fecha_asignacion' => $asignacion->fecha_asignacion,
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $usuarios
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo usuarios asignados', [
                'sesion_id' => $sesion->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo usuarios asignados'
            ], 500);
        }
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function edit(SesionJuicio $sesion)
    {
        return view('sesiones.edit', compact('sesion'));
    }

    /**
     * Actualizar sesión existente
     */
    public function update(Request $request, SesionJuicio $sesion)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:civil,penal,laboral,administrativo',
            'fecha_inicio' => 'required|date',
            'max_participantes' => 'nullable|integer|min:1|max:20',
            'estado' => 'required|in:programada,en_curso,finalizada,cancelada',
        ]);

        try {
            $sesion->update($request->all());

            return redirect()
                ->route('sesiones.show', $sesion)
                ->with('success', 'Sesión actualizada exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la sesión: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar sesión
     */
    public function destroy(SesionJuicio $sesion)
    {
        try {
            // Verificar si la sesión está en curso
            if ($sesion->estado === 'en_curso') {
                return redirect()
                    ->back()
                    ->with('error', 'No se puede eliminar una sesión que está en curso');
            }

            $sesion->delete();

            return redirect()
                ->route('sesiones.index')
                ->with('success', 'Sesión eliminada exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la sesión: ' . $e->getMessage());
        }
    }

    /**
     * Iniciar sesión
     */
    public function iniciar(SesionJuicio $sesion)
    {
        try {
            if ($sesion->estado !== 'programada') {
                return redirect()
                    ->back()
                    ->with('error', 'Solo se pueden iniciar sesiones programadas');
            }

            $sesion->update([
                'estado' => 'en_curso',
                'fecha_inicio' => now(),
            ]);

            return redirect()
                ->back()
                ->with('success', 'Sesión iniciada exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al iniciar la sesión: ' . $e->getMessage());
        }
    }

    /**
     * Finalizar sesión
     */
    public function finalizar(SesionJuicio $sesion)
    {
        try {
            if ($sesion->estado !== 'en_curso') {
                return redirect()
                    ->back()
                    ->with('error', 'Solo se pueden finalizar sesiones en curso');
            }

            $sesion->update([
                'estado' => 'finalizada',
                'fecha_fin' => now(),
            ]);

            return redirect()
                ->back()
                ->with('success', 'Sesión finalizada exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al finalizar la sesión: ' . $e->getMessage());
        }
    }
}
