<?php

namespace App\Services;

use App\Models\SesionJuicio;
use App\Models\SesionDialogo;
use App\Models\DecisionSesion;
use App\Models\NodoDialogo;
use App\Models\RespuestaDialogo;
use App\Models\User;
use App\Models\RolDisponible;
use Illuminate\Support\Facades\Log;

class ProcesamientoAutomaticoService
{
    /**
     * Procesar decisiones automáticas para roles vacíos
     */
    public function procesarDecisionesAutomaticas(SesionJuicio $sesion)
    {
        try {
            $sesionDialogo = $sesion->dialogoActivo();
            if (!$sesionDialogo) {
                return false;
            }

            $nodoActual = $sesionDialogo->nodoActual;
            if (!$nodoActual) {
                return false;
            }

            // Obtener respuestas disponibles para este nodo
            $respuestasDisponibles = $nodoActual->respuestas()->where('activo', true)->get();
            
            if ($respuestasDisponibles->isEmpty()) {
                return false;
            }

            // Obtener roles que no tienen asignación o están vacíos
            $rolesVacios = $this->obtenerRolesVacios($sesion);
            
            foreach ($rolesVacios as $rol) {
                $this->procesarDecisionParaRol($sesion, $sesionDialogo, $nodoActual, $respuestasDisponibles, $rol);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error en procesamiento automático: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener roles que están vacíos o sin asignación
     */
    private function obtenerRolesVacios(SesionJuicio $sesion)
    {
        $rolesAsignados = $sesion->asignaciones()->pluck('rol_id')->toArray();
        $todosLosRoles = RolDisponible::where('activo', true)->pluck('id')->toArray();
        
        $rolesVacios = array_diff($todosLosRoles, $rolesAsignados);
        
        return RolDisponible::whereIn('id', $rolesVacios)->get();
    }

    /**
     * Procesar decisión automática para un rol específico
     */
    private function procesarDecisionParaRol(SesionJuicio $sesion, SesionDialogo $sesionDialogo, NodoDialogo $nodoActual, $respuestasDisponibles, RolDisponible $rol)
    {
        // Seleccionar respuesta aleatoria
        $respuestaSeleccionada = $respuestasDisponibles->random();
        
        // Crear decisión automática
        DecisionSesion::create([
            'sesion_id' => $sesion->id,
            'usuario_id' => null, // Usuario nulo para decisiones automáticas
            'rol_id' => $rol->id,
            'nodo_dialogo_id' => $nodoActual->id,
            'respuesta_dialogo_id' => $respuestaSeleccionada->id,
            'texto_respuesta' => $respuestaSeleccionada->texto,
            'tiempo_respuesta' => rand(5, 15), // Tiempo aleatorio entre 5-15 segundos
            'es_automatica' => true,
            'notas' => "Decisión automática para rol: {$rol->nombre}"
        ]);

        Log::info("Decisión automática creada para rol {$rol->nombre}: {$respuestaSeleccionada->texto}");
    }

    /**
     * Asignar estudiante automáticamente a un rol vacío
     */
    public function asignarEstudianteAutomatico(SesionJuicio $sesion, RolDisponible $rol)
    {
        try {
            // Obtener estudiantes disponibles que no estén ya asignados
            $estudiantesAsignados = $sesion->asignaciones()->pluck('usuario_id')->toArray();
            
            $estudianteDisponible = User::where('tipo', 'alumno')
                ->where('activo', true)
                ->whereNotIn('id', $estudiantesAsignados)
                ->inRandomOrder()
                ->first();

            if ($estudianteDisponible) {
                $sesion->asignaciones()->create([
                    'usuario_id' => $estudianteDisponible->id,
                    'rol_id' => $rol->id,
                    'asignado_por' => $sesion->instructor_id,
                    'confirmado' => false,
                    'notas' => 'Asignación automática del sistema'
                ]);

                Log::info("Estudiante {$estudianteDisponible->name} asignado automáticamente al rol {$rol->nombre}");
                return $estudianteDisponible;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error asignando estudiante automático: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Procesar todas las asignaciones automáticas de una sesión
     */
    public function procesarAsignacionesAutomaticas(SesionJuicio $sesion)
    {
        try {
            $rolesDisponibles = RolDisponible::where('activo', true)->get();
            $asignacionesRealizadas = [];

            foreach ($rolesDisponibles as $rol) {
                $asignacionExistente = $sesion->asignaciones()->where('rol_id', $rol->id)->first();
                
                if (!$asignacionExistente) {
                    $estudiante = $this->asignarEstudianteAutomatico($sesion, $rol);
                    if ($estudiante) {
                        $asignacionesRealizadas[] = [
                            'rol' => $rol->nombre,
                            'estudiante' => $estudiante->name,
                            'email' => $estudiante->email
                        ];
                    }
                }
            }

            return $asignacionesRealizadas;

        } catch (\Exception $e) {
            Log::error('Error procesando asignaciones automáticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Simular participación automática en el diálogo
     */
    public function simularParticipacionAutomatica(SesionJuicio $sesion)
    {
        try {
            $sesionDialogo = $sesion->dialogoActivo();
            if (!$sesionDialogo) {
                return false;
            }

            $nodoActual = $sesionDialogo->nodoActual;
            if (!$nodoActual) {
                return false;
            }

            // Verificar si este nodo requiere decisión
            if ($nodoActual->tipo === 'decision') {
                $this->procesarDecisionesAutomaticas($sesion);
            }

        } catch (\Exception $e) {
            Log::error('Error simulando participación automática: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa asignaciones automáticas para roles de un diálogo específico
     */
    public function procesarAsignacionesAutomaticasDialogo(SesionJuicio $sesion, $rolesDialogo)
    {
        $asignacionesRealizadas = [];

        foreach ($rolesDialogo as $rolDialogo) {
            // Solo procesar roles requeridos automáticamente
            if ($rolDialogo->requerido) {
                $asignacion = $this->asignarEstudianteAutomaticoDialogo($sesion, $rolDialogo);
                if ($asignacion) {
                    $asignacionesRealizadas[] = $asignacion;
                }
            }
        }

        return $asignacionesRealizadas;
    }

    /**
     * Asigna un estudiante aleatorio disponible a un rol de diálogo en una sesión
     */
    public function asignarEstudianteAutomaticoDialogo(SesionJuicio $sesion, $rolDialogo): ?\App\Models\AsignacionRol
    {
        // Obtener estudiantes ya asignados en esta sesión
        $estudiantesAsignadosIds = \App\Models\AsignacionRol::where('sesion_id', $sesion->id)
            ->pluck('usuario_id')
            ->toArray();

        // Obtener un estudiante de tipo 'alumno' que esté activo y no esté ya asignado
        $estudianteDisponible = User::where('tipo', 'alumno')
            ->where('activo', true)
            ->whereNotIn('id', $estudiantesAsignadosIds)
            ->inRandomOrder()
            ->first();

        if ($estudianteDisponible) {
            $asignacion = \App\Models\AsignacionRol::create([
                'sesion_id' => $sesion->id,
                'usuario_id' => $estudianteDisponible->id,
                'rol_dialogo_id' => $rolDialogo->id,
                'asignado_por' => auth()->id() ?? $sesion->instructor_id,
                'confirmado' => false,
                'notas' => 'Asignación automática del sistema'
            ]);
            Log::info("Asignación automática: Estudiante {$estudianteDisponible->name} ({$estudianteDisponible->email}) asignado al rol {$rolDialogo->nombre} en la sesión {$sesion->nombre}.");
            return $asignacion;
        }

        Log::warning("Asignación automática: No se encontró estudiante disponible para el rol {$rolDialogo->nombre} en la sesión {$sesion->nombre}. El rol queda sin asignar.");
        return null;
    }
}
