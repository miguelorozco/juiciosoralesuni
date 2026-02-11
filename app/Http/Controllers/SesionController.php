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
use Carbon\Carbon;

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
        \Log::info('=== INICIO STORE SESIÓN ===');
        \Log::info('Method: ' . $request->method());
        \Log::info('URL: ' . $request->fullUrl());
        \Log::info('User ID: ' . (auth()->id() ?? 'null'));
        \Log::info('Request All: ' . json_encode($request->all()));
        \Log::info('CSRF Token Present: ' . ($request->has('_token') ? 'yes' : 'no'));
        
        try {
            \Log::info('Iniciando validación...');
            
            // Validar campos básicos primero
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'tipo' => 'required|in:civil,penal,laboral,administrativo',
                'fecha_inicio' => 'required|date_format:Y-m-d\TH:i',
                'fecha_fin' => 'nullable|date_format:Y-m-d\TH:i',
                'max_participantes' => 'nullable|integer|min:1|max:20',
                'dialogo_id' => 'required|exists:dialogos_v2,id',
            ]);
            
            // Validar que fecha_fin sea mayor o igual que fecha_inicio manualmente
            if ($request->fecha_fin) {
                try {
                    $fechaInicio = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_inicio);
                    $fechaFin = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_fin);
                    
                    if ($fechaFin->lt($fechaInicio)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'fecha_fin' => 'La fecha de fin debe ser mayor o igual a la fecha de inicio.'
                        ]);
                    }
                } catch (\Exception $e) {
                    // Si hay error parseando, ya fue validado con date_format arriba
                    \Log::warning('Error en validación manual de fechas: ' . $e->getMessage());
                }
            }
            
            \Log::info('Validación exitosa');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación: ' . json_encode($e->errors()));
            throw $e;
        }

        try {
            \Log::info('Iniciando transacción de base de datos...');
            \DB::beginTransaction();

            // Parsear fechas correctamente con timezone
            // datetime-local envía formato "Y-m-d\TH:i" sin timezone, lo parseamos como local y luego convertimos a UTC
            \Log::info('Parseando fechas...');
            \Log::info('fecha_inicio raw: ' . ($request->fecha_inicio ?? 'null'));
            \Log::info('fecha_fin raw: ' . ($request->fecha_fin ?? 'null'));
            
            $fechaInicio = null;
            $fechaFin = null;
            
            if ($request->fecha_inicio) {
                try {
                    \Log::info('Intentando parsear fecha_inicio con formato específico...');
                    // datetime-local envía formato "Y-m-d\TH:i" sin timezone
                    // Parseamos sin timezone primero, luego establecemos UTC
                    $fechaInicio = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_inicio);
                    // Asumimos que la fecha viene en UTC (o la zona horaria del servidor)
                    // y la mantenemos en UTC para guardar en la base de datos
                    $fechaInicio->setTimezone('UTC');
                    \Log::info('fecha_inicio parseada: ' . $fechaInicio->toDateTimeString() . ' (UTC)');
                } catch (\Exception $e) {
                    \Log::warning('Error parseando fecha_inicio con formato específico: ' . $e->getMessage());
                    // Si falla el formato específico, intentar parseo genérico
                    try {
                        // Parsear sin timezone y luego establecer UTC
                        $fechaInicio = Carbon::parse($request->fecha_inicio);
                        $fechaInicio->setTimezone('UTC');
                        \Log::info('fecha_inicio parseada con método genérico: ' . $fechaInicio->toDateTimeString() . ' (UTC)');
                    } catch (\Exception $e2) {
                        \Log::error('Error parseando fecha_inicio con método genérico: ' . $e2->getMessage());
                        throw $e2;
                    }
                }
            }
            
            if ($request->fecha_fin) {
                try {
                    \Log::info('Intentando parsear fecha_fin con formato específico...');
                    // datetime-local envía formato "Y-m-d\TH:i" sin timezone
                    // Parseamos sin timezone primero, luego establecemos UTC
                    $fechaFin = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_fin);
                    // Asumimos que la fecha viene en UTC (o la zona horaria del servidor)
                    // y la mantenemos en UTC para guardar en la base de datos
                    $fechaFin->setTimezone('UTC');
                    \Log::info('fecha_fin parseada: ' . $fechaFin->toDateTimeString() . ' (UTC)');
                } catch (\Exception $e) {
                    \Log::warning('Error parseando fecha_fin con formato específico: ' . $e->getMessage());
                    // Si falla el formato específico, intentar parseo genérico
                    try {
                        // Parsear sin timezone y luego establecer UTC
                        $fechaFin = Carbon::parse($request->fecha_fin);
                        $fechaFin->setTimezone('UTC');
                        \Log::info('fecha_fin parseada con método genérico: ' . $fechaFin->toDateTimeString() . ' (UTC)');
                    } catch (\Exception $e2) {
                        \Log::error('Error parseando fecha_fin con método genérico: ' . $e2->getMessage());
                        throw $e2;
                    }
                }
            }

            // Crear la sesión
            \Log::info('Creando sesión en base de datos...');
            $dataSesion = [
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'tipo' => $request->tipo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'max_participantes' => $request->max_participantes ?? 10,
                'instructor_id' => auth()->id(),
                'estado' => 'programada',
            ];
            \Log::info('Datos de sesión a crear: ' . json_encode($dataSesion));
            
            $sesion = SesionJuicio::create($dataSesion);
            \Log::info('Sesión creada con ID: ' . $sesion->id);

            // Obtener roles del diálogo seleccionado
            \Log::info('Obteniendo diálogo con ID: ' . $request->dialogo_id);
            $dialogo = \App\Models\DialogoV2::find($request->dialogo_id);
            \Log::info('Diálogo encontrado: ' . ($dialogo ? 'Sí - ' . $dialogo->nombre : 'No'));
            
            // Nota: Los roles ahora se obtienen de roles_disponibles directamente
            $rolesDialogo = collect(); // Se puede obtener de otra forma si es necesario

            // Procesar asignaciones automáticas de roles del diálogo
            \Log::info('Procesando asignaciones automáticas...');
            $procesamientoService = new ProcesamientoAutomaticoService();
            $asignacionesRealizadas = $procesamientoService->procesarAsignacionesAutomaticasDialogo($sesion, $rolesDialogo);
            \Log::info('Asignaciones realizadas: ' . count($asignacionesRealizadas));

            // Crear sesión de diálogo
            \Log::info('Creando sesión de diálogo...');
            $this->crearSesionDialogo($sesion, $request->dialogo_id);
            \Log::info('Sesión de diálogo creada');

            \Log::info('Haciendo commit de la transacción...');
            \DB::commit();
            \Log::info('Transacción completada exitosamente');

            $mensaje = 'Sesión creada exitosamente. ';
            if (count($asignacionesRealizadas) > 0) {
                $mensaje .= 'Asignaciones automáticas realizadas: ' . count($asignacionesRealizadas) . ' roles asignados.';
            }

            \Log::info('Redirigiendo a sesiones.show con ID: ' . $sesion->id);
            \Log::info('=== FIN STORE SESIÓN (ÉXITO) ===');
            
            return redirect()
                ->route('sesiones.show', $sesion)
                ->with('success', $mensaje);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('=== ERROR DE VALIDACIÓN ===');
            \Log::error('Errores: ' . json_encode($e->errors()));
            \Log::error('Request: ' . json_encode($request->all()));
            \DB::rollback();
            \Log::info('=== FIN STORE SESIÓN (VALIDACIÓN FALLIDA) ===');
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('=== ERROR GENERAL EN STORE SESIÓN ===');
            \Log::error('Mensaje: ' . $e->getMessage());
            \Log::error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Trace: ' . $e->getTraceAsString());
            \Log::error('Request: ' . json_encode($request->all()));
            \DB::rollback();
            \Log::info('=== FIN STORE SESIÓN (ERROR) ===');
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
        \Log::info('=== CREAR SESIÓN DIÁLOGO ===');
        \Log::info('Sesión ID: ' . $sesion->id);
        \Log::info('Diálogo ID: ' . $dialogoId);
        
        try {
            $dialogo = \App\Models\DialogoV2::findOrFail($dialogoId);
            \Log::info('Diálogo encontrado: ' . $dialogo->nombre);
            
            $nodoInicial = $dialogo->nodo_inicial;
            \Log::info('Nodo inicial ID: ' . ($nodoInicial ? $nodoInicial->id : 'null'));
            
            $dataSesionDialogo = [
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
            ];
            
            \Log::info('Creando SesionDialogoV2 con datos: ' . json_encode($dataSesionDialogo));
            $sesionDialogo = \App\Models\SesionDialogoV2::create($dataSesionDialogo);
            \Log::info('SesionDialogoV2 creada con ID: ' . $sesionDialogo->id);
            \Log::info('=== FIN CREAR SESIÓN DIÁLOGO (ÉXITO) ===');
        } catch (\Exception $e) {
            \Log::error('=== ERROR EN CREAR SESIÓN DIÁLOGO ===');
            \Log::error('Mensaje: ' . $e->getMessage());
            \Log::error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
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
     * Obtener roles disponibles de una sesión con información de ocupación
     */
    public function getRolesDisponibles(SesionJuicio $sesion)
    {
        try {
            // Obtener el diálogo asociado a la sesión
            $sesionDialogo = SesionDialogoV2::where('sesion_id', $sesion->id)
                ->with('dialogo')
                ->first();

            if (!$sesionDialogo || !$sesionDialogo->dialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta sesión no tiene un diálogo asignado'
                ], 404);
            }

            // Obtener roles del diálogo
            $rolesDialogo = \DB::table('nodos_dialogo_v2')
                ->join('roles_disponibles', 'nodos_dialogo_v2.rol_id', '=', 'roles_disponibles.id')
                ->where('nodos_dialogo_v2.dialogo_id', $sesionDialogo->dialogo_id)
                ->select('roles_disponibles.*')
                ->distinct()
                ->get();

            // Obtener asignaciones de roles para esta sesión
            $asignaciones = AsignacionRol::with(['usuario', 'rolDisponible'])
                ->where('sesion_id', $sesion->id)
                ->get()
                ->keyBy('rol_id');

            $currentUserId = auth()->check() ? auth()->id() : null;

            // Mapear roles con información de ocupación
            $roles = $rolesDialogo->map(function ($rol) use ($asignaciones, $currentUserId) {
                $asignacion = $asignaciones->get($rol->id);
                $assignedUserId = $asignacion ? $asignacion->usuario_id : null;
                $isOwnRole = $currentUserId && $assignedUserId && $assignedUserId === $currentUserId;
                $isOccupiedByOther = $assignedUserId && (!$currentUserId || $assignedUserId !== $currentUserId);
                
                return [
                    'id' => $rol->id,
                    'nombre' => $rol->nombre,
                    'descripcion' => $rol->descripcion,
                    'color' => $rol->color,
                    'icono' => $rol->icono,
                    'orden' => $rol->orden,
                    'ocupado_por' => $isOccupiedByOther && $asignacion && $asignacion->usuario
                        ? $asignacion->usuario->name
                        : null,
                    'usuario_id' => $assignedUserId,
                    'is_own_role' => $isOwnRole,
                    'is_occupied_by_other' => $isOccupiedByOther,
                ];
            })->sortBy('orden')->values();

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo roles disponibles', [
                'sesion_id' => $sesion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo roles disponibles: ' . $e->getMessage()
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
        $rolesConDecision = [];
        $conteoNodosPorRol = [];
        
        if ($dialogoId) {
            // Obtener roles únicos del diálogo
            $rolesEnDialogo = NodoDialogoV2::where('dialogo_id', $dialogoId)
                ->whereNotNull('rol_id')
                ->pluck('rol_id')
                ->unique()
                ->toArray();
                
            if (!empty($rolesEnDialogo)) {
                $roles = RolDisponible::activos()->ordenados()->whereIn('id', $rolesEnDialogo)->get();
            }
            
            // Identificar roles que tienen nodos de decisión
            $rolesConDecision = NodoDialogoV2::where('dialogo_id', $dialogoId)
                ->whereNotNull('rol_id')
                ->where('tipo', 'decision')
                ->pluck('rol_id')
                ->unique()
                ->toArray();
            
            // Contar nodos por rol para mostrar participación
            $conteoNodosPorRol = NodoDialogoV2::where('dialogo_id', $dialogoId)
                ->whereNotNull('rol_id')
                ->selectRaw('rol_id, COUNT(*) as total, SUM(CASE WHEN tipo = "decision" THEN 1 ELSE 0 END) as decisiones')
                ->groupBy('rol_id')
                ->get()
                ->keyBy('rol_id')
                ->toArray();
        }

        // Participantes que se pueden asignar a roles: alumnos e instructores (y admin)
        $participantesDisponibles = User::whereIn('tipo', ['alumno', 'instructor', 'admin'])
            ->where('activo', true)
            ->orderByRaw("CASE tipo WHEN 'admin' THEN 1 WHEN 'instructor' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();
        $asignaciones = $sesion->asignaciones()->with('usuario')->get()->keyBy('rol_id');
        // Usuarios que pueden iniciar el diálogo (instructor de la sesión): admin e instructores
        $instructoresDisponibles = User::whereIn('tipo', ['admin', 'instructor'])
            ->where('activo', true)
            ->orderBy('tipo')
            ->orderBy('name')
            ->get();

        return view('sesiones.edit', compact(
            'sesion', 'roles', 'participantesDisponibles', 'asignaciones',
            'dialogoActivo', 'dialogos', 'dialogoId',
            'rolesConDecision', 'conteoNodosPorRol',
            'instructoresDisponibles'
        ));
    }

    /**
     * Actualizar sesión existente
     */
    public function update(Request $request, SesionJuicio $sesion)
    {
        // Validar campos básicos primero
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:civil,penal,laboral,administrativo',
            'fecha_inicio' => 'required|date_format:Y-m-d\TH:i',
            'fecha_fin' => 'nullable|date_format:Y-m-d\TH:i',
            'max_participantes' => 'nullable|integer|min:1|max:20',
            'estado' => 'required|in:programada,en_curso,finalizada,cancelada',
            'dialogo_id' => 'required|exists:dialogos_v2,id',
            'instructor_id' => 'required|exists:users,id',
            'asignaciones' => 'nullable|array',
            'asignaciones.*' => 'nullable|exists:users,id',
        ]);

        // Solo admin o instructor pueden ser instructor de la sesión (pueden iniciar el diálogo)
        $instructor = User::find($request->instructor_id);
        if (!$instructor || !in_array($instructor->tipo, ['admin', 'instructor'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'instructor_id' => 'El instructor debe ser un usuario con rol Admin o Instructor.',
            ]);
        }
        
        // Validar que fecha_fin sea mayor o igual que fecha_inicio manualmente
        if ($request->fecha_fin) {
            try {
                $fechaInicio = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_inicio);
                $fechaFin = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_fin);
                
                if ($fechaFin->lt($fechaInicio)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fecha_fin' => 'La fecha de fin debe ser mayor o igual a la fecha de inicio.'
                    ]);
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw $e;
            } catch (\Exception $e) {
                // Si hay error parseando, ya fue validado con date_format arriba
            }
        }

        try {
            // Parsear fechas correctamente con timezone
            // datetime-local envía formato "Y-m-d\TH:i" sin timezone, lo parseamos como local y luego convertimos a UTC
            $data = $request->only(['nombre','descripcion','tipo','max_participantes','estado','instructor_id']);
            
            if ($request->fecha_inicio) {
                // Parsear sin timezone primero, luego establecer UTC
                $data['fecha_inicio'] = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_inicio)->setTimezone('UTC');
            }
            
            if ($request->fecha_fin) {
                // Parsear sin timezone primero, luego establecer UTC
                $data['fecha_fin'] = Carbon::createFromFormat('Y-m-d\TH:i', $request->fecha_fin)->setTimezone('UTC');
            }
            
            $sesion->update($data);

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
