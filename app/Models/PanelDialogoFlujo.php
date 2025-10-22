<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelDialogoFlujo extends Model
{
    use HasFactory;

    protected $table = 'panel_dialogo_flujos';

    protected $fillable = [
        'escenario_id',
        'rol_id',
        'nombre',
        'descripcion',
        'activo',
        'orden',
        'configuracion'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoEscenario::class, 'escenario_id');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoRol::class, 'rol_id');
    }

    public function dialogos(): HasMany
    {
        return $this->hasMany(PanelDialogoDialogo::class, 'flujo_id')->orderBy('orden');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorRol($query, int $rolId)
    {
        return $query->where('rol_id', $rolId);
    }

    public function scopePorEscenario($query, int $escenarioId)
    {
        return $query->where('escenario_id', $escenarioId);
    }

    // Accessors
    public function getTotalDialogosAttribute(): int
    {
        return $this->dialogos()->count();
    }

    public function getDialogoInicialAttribute(): ?PanelDialogoDialogo
    {
        return $this->dialogos()->where('es_inicial', true)->first();
    }

    public function getDialogosFinalesAttribute()
    {
        return $this->dialogos()->where('es_final', true)->get();
    }

    public function getDialogosDeDecisionAttribute()
    {
        return $this->dialogos()->where('tipo', 'decision')->get();
    }

    // Métodos
    public function obtenerSecuenciaDialogos(): array
    {
        $dialogos = $this->dialogos()->with(['opciones'])->get();
        
        $secuencia = [];
        $dialogoInicial = $dialogos->where('es_inicial', true)->first();
        
        if ($dialogoInicial) {
            $secuencia = $this->construirSecuencia($dialogoInicial, $dialogos);
        }
        
        return $secuencia;
    }

    private function construirSecuencia(PanelDialogoDialogo $dialogoActual, $todosLosDialogos, $visitados = []): array
    {
        if (in_array($dialogoActual->id, $visitados)) {
            return []; // Evitar ciclos infinitos
        }

        $visitados[] = $dialogoActual->id;
        $secuencia = [
            'dialogo' => $dialogoActual,
            'opciones' => $dialogoActual->opciones,
            'siguientes' => []
        ];

        if ($dialogoActual->tipo === 'decision') {
            foreach ($dialogoActual->opciones as $opcion) {
                $dialogoSiguiente = $todosLosDialogos->where('id', $opcion->dialogo_siguiente_id)->first();
                if ($dialogoSiguiente) {
                    $secuencia['siguientes'][] = $this->construirSecuencia($dialogoSiguiente, $todosLosDialogos, $visitados);
                }
            }
        }

        return $secuencia;
    }

    public function validarEstructura(): array
    {
        $errores = [];
        $dialogos = $this->dialogos;

        // Verificar que hay al menos un diálogo inicial
        if ($dialogos->where('es_inicial', true)->count() === 0) {
            $errores[] = 'El flujo debe tener al menos un diálogo inicial';
        }

        // Verificar que hay al menos un diálogo final
        if ($dialogos->where('es_final', true)->count() === 0) {
            $errores[] = 'El flujo debe tener al menos un diálogo final';
        }

        // Verificar que los diálogos de decisión tienen opciones
        foreach ($dialogos->where('tipo', 'decision') as $dialogo) {
            if ($dialogo->opciones->count() === 0) {
                $errores[] = "El diálogo '{$dialogo->titulo}' de tipo decisión debe tener al menos una opción";
            }
        }

        // Verificar que no hay nodos huérfanos
        $dialogosConConexiones = $dialogos->pluck('id')->toArray();
        foreach ($dialogos as $dialogo) {
            if (!$dialogo->es_inicial && !$dialogo->conexionesEntrantes->count()) {
                $errores[] = "El diálogo '{$dialogo->titulo}' no tiene conexiones entrantes y no es inicial";
            }
        }

        return $errores;
    }

    public function obtenerEstadisticas(): array
    {
        $dialogos = $this->dialogos;
        
        return [
            'total_dialogos' => $dialogos->count(),
            'dialogos_automaticos' => $dialogos->where('tipo', 'automatico')->count(),
            'dialogos_decision' => $dialogos->where('tipo', 'decision')->count(),
            'dialogos_finales' => $dialogos->where('tipo', 'final')->count(),
            'total_opciones' => $dialogos->sum(function($dialogo) {
                return $dialogo->opciones->count();
            }),
            'complejidad' => $this->calcularComplejidad($dialogos)
        ];
    }

    private function calcularComplejidad($dialogos): string
    {
        $totalDialogos = $dialogos->count();
        $totalOpciones = $dialogos->sum(function($dialogo) {
            return $dialogo->opciones->count();
        });

        if ($totalDialogos <= 3 && $totalOpciones <= 6) return 'Baja';
        if ($totalDialogos <= 8 && $totalOpciones <= 15) return 'Media';
        return 'Alta';
    }
}