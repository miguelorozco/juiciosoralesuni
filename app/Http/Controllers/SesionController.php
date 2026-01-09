<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesionJuicio;
use App\Models\AsignacionRol;
use App\Models\RolDisponible;
use App\Models\DialogoV2;
use App\Models\NodoDialogoV2;
use App\Models\SesionDialogoV2;
use App\Models\User;
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
        $base = SesionJuicio::with(['instructor', 'plantilla'])->orderBy('fecha_inicio', 'desc');

        // Filtros opcionales
        if ($request->filled('tipo')) {
            $base->where('tipo', $request->tipo);
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $base->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', '%' . $buscar . '%')
                  ->orWhere('descripcion', 'like', '%' . $buscar . '%')
                  ->orWhereHas('instructor', function($instructorQuery) use ($buscar) {
                      $instructorQuery->where('name', 'like', '%' . $buscar . '%');
                  });
            });
        }

        // Colecciones por estado
        $sesionesPorIniciar = (clone $base)->where('estado', 'programada')->get();
        $sesionesIniciadas  = (clone $base)->where('estado', 'en_curso')->get();
        $sesionesTerminadas = (clone $base)->where('estado', 'finalizada')->get();
        $sesionesCanceladas = (clone $base)->where('estado', 'cancelada')->get();

        // Listado general (mantener vista existente)
        $sesiones = $base->paginate($request->get('per_page', 20));

        return view('sesiones.index', compact(
            'sesiones',
            'sesionesPorIniciar',
            'sesionesIniciadas',
            'sesionesTerminadas',
            'sesionesCanceladas'
        ));
    }
    
    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $dialogos = \App\Models\DialogoV2::where('estado', 'activo')
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
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'max_participantes' => 'nullable|integer|min:1|max:20',
            'dialogo_id' => 'required|exists:dialogos_v2,id',
        ]);

        try {
            \DB::beginTransaction();

            // Crear la sesión
            $sesion = SesionJuicio::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'tipo' => $request->tipo,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'max_participantes' => $request->max_participantes ?? 10,
                'instructor_id' => auth()->id(),
                'estado' => 'programada',
            ]);

            // Obtener roles del diálogo seleccionado
            $dialogo = \App\Models\DialogoV2::find($request->dialogo_id);
            // Nota: Los roles ahora se obtienen de roles_disponibles directamente
            $rolesDialogo = collect(); // Se puede obtener de otra forma si es necesario

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
        $dialogo = \App\Models\DialogoV2::findOrFail($dialogoId);
        $nodoInicial = $dialogo->nodo_inicial;
        
        \App\Models\SesionDialogoV2::create([
            'sesion_id' => $sesion->id,
            'dialogo_id' => $dialogoId,
            'estado' => 'iniciado',
            'nodo_actual_id' => $nodoInicial ? $nodoInicial->id : null,
            'configuracion' => [
                'modo_automatico' => true,
                'tiempo_respuesta' => 30,
                'permite_pausa' => true
            ],
            'variables' => [],
            'historial_nodos' => [],
            'audio_habilitado' => false,
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
        $dialogos = DialogoV2::activos()->publicos()->orderBy('nombre')->get();
        $dialogoActivo = optional($sesion->dialogos()->with('dialogo')->latest()->first())->dialogo;
        $dialogoId = $dialogoActivo?->id;

        // Roles usados en el diálogo seleccionado (por rol_id de nodos)
        $roles = collect();
        if ($dialogoId) {
            $rolesEnDialogo = NodoDialogoV2::where('dialogo_id', $dialogoId)
                ->whereNotNull('rol_id')
                ->pluck('rol_id')
                ->unique()
                ->toArray();
            if (!empty($rolesEnDialogo)) {
                $roles = RolDisponible::activos()->ordenados()->whereIn('id', $rolesEnDialogo)->get();
            }
        }

        $alumnos = User::where('tipo', 'alumno')->where('activo', true)->orderBy('name')->get();
        $asignaciones = $sesion->asignaciones()->with('usuario')->get()->keyBy('rol_id');

        return view('sesiones.edit', compact('sesion', 'roles', 'alumnos', 'asignaciones', 'dialogoActivo', 'dialogos', 'dialogoId'));
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
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'max_participantes' => 'nullable|integer|min:1|max:20',
            'estado' => 'required|in:programada,en_curso,finalizada,cancelada',
            'dialogo_id' => 'required|exists:dialogos_v2,id',
            'asignaciones' => 'nullable|array',
            'asignaciones.*' => 'nullable|exists:users,id',
        ]);

        try {
            $sesion->update($request->only([
                'nombre','descripcion','tipo','fecha_inicio','fecha_fin','max_participantes','estado'
            ]));

            $dialogoId = $request->input('dialogo_id');
            if ($dialogoId) {
                $dialogo = DialogoV2::find($dialogoId);
                $nodoInicial = $dialogo?->nodo_inicial;

                $sesionDialogo = SesionDialogoV2::firstOrNew(['sesion_id' => $sesion->id]);
                $sesionDialogo->dialogo_id = $dialogoId;
                $sesionDialogo->nodo_actual_id = $nodoInicial?->id;
                $sesionDialogo->estado = $sesionDialogo->estado ?? 'iniciado';
                $sesionDialogo->configuracion = $sesionDialogo->configuracion ?? ['modo_automatico' => true, 'tiempo_respuesta' => 30, 'permite_pausa' => true];
                $sesionDialogo->save();
            }

            // Roles válidos según el diálogo seleccionado
            $rolesValidos = [];
            if ($dialogoId) {
                $rolesValidos = NodoDialogoV2::where('dialogo_id', $dialogoId)
                    ->whereNotNull('rol_id')
                    ->pluck('rol_id')
                    ->unique()
                    ->toArray();
            }

            // Limpiar asignaciones que no estén en roles válidos
            if (!empty($rolesValidos)) {
                AsignacionRol::where('sesion_id', $sesion->id)
                    ->whereNotIn('rol_id', $rolesValidos)
                    ->delete();
            }

            // Actualizar asignaciones de roles (rol_id => usuario_id | null)
            $asignaciones = $request->input('asignaciones', []);
            // Filtrar solo roles válidos del diálogo
            if (!empty($rolesValidos)) {
                $asignaciones = array_filter($asignaciones, function($usuarioId, $rolId) use ($rolesValidos) {
                    return in_array((int)$rolId, $rolesValidos);
                }, ARRAY_FILTER_USE_BOTH);
            }

            foreach ($asignaciones as $rolId => $usuarioId) {
                if (empty($usuarioId)) {
                    AsignacionRol::where('sesion_id', $sesion->id)
                        ->where('rol_id', $rolId)
                        ->delete();
                    continue;
                }

                AsignacionRol::updateOrCreate(
                    [
                        'sesion_id' => $sesion->id,
                        'rol_id' => $rolId,
                    ],
                    [
                        'usuario_id' => $usuarioId,
                        'asignado_por' => auth()->id(),
                        'fecha_asignacion' => now(),
                        'confirmado' => false,
                    ]
                );
            }

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
