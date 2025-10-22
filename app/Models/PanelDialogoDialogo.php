<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelDialogoDialogo extends Model
{
    use HasFactory;

    protected $table = 'panel_dialogo_dialogos';

    protected $fillable = [
        'flujo_id',
        'titulo',
        'contenido',
        'tipo',
        'es_inicial',
        'es_final',
        'orden',
        'posicion',
        'configuracion'
    ];

    protected $casts = [
        'posicion' => 'array',
        'configuracion' => 'array',
        'es_inicial' => 'boolean',
        'es_final' => 'boolean',
    ];

    // Relaciones
    public function flujo(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoFlujo::class, 'flujo_id');
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(PanelDialogoOpcion::class, 'dialogo_id')->orderBy('orden');
    }

    public function opcionesActivas(): HasMany
    {
        return $this->hasMany(PanelDialogoOpcion::class, 'dialogo_id')->where('activo', true)->orderBy('orden');
    }

    public function conexionesSalientes(): HasMany
    {
        return $this->hasMany(PanelDialogoConexion::class, 'dialogo_origen_id');
    }

    public function conexionesEntrantes(): HasMany
    {
        return $this->hasMany(PanelDialogoConexion::class, 'dialogo_destino_id');
    }

    public function decisiones(): HasMany
    {
        return $this->hasMany(PanelDialogoDecision::class, 'dialogo_id');
    }

    // Scopes
    public function scopeIniciales($query)
    {
        return $query->where('es_inicial', true);
    }

    public function scopeFinales($query)
    {
        return $query->where('es_final', true);
    }

    public function scopeAutomaticos($query)
    {
        return $query->where('tipo', 'automatico');
    }

    public function scopeDecisiones($query)
    {
        return $query->where('tipo', 'decision');
    }

    public function scopePorFlujo($query, int $flujoId)
    {
        return $query->where('flujo_id', $flujoId);
    }

    // Accessors
    public function getTotalOpcionesAttribute(): int
    {
        return $this->opciones()->count();
    }

    public function getTotalConexionesSalientesAttribute(): int
    {
        return $this->conexionesSalientes()->count();
    }

    public function getTotalConexionesEntrantesAttribute(): int
    {
        return $this->conexionesEntrantes()->count();
    }

    public function getPosicionXAttribute(): float
    {
        return $this->posicion['x'] ?? 0;
    }

    public function getPosicionYAttribute(): float
    {
        return $this->posicion['y'] ?? 0;
    }

    public function getTieneOpcionesAttribute(): bool
    {
        return $this->opciones()->count() > 0;
    }

    public function getEsDecisionAttribute(): bool
    {
        return $this->tipo === 'decision';
    }

    public function getEsAutomaticoAttribute(): bool
    {
        return $this->tipo === 'automatico';
    }

    // Métodos
    public function actualizarPosicion(float $x, float $y): void
    {
        $this->update([
            'posicion' => ['x' => $x, 'y' => $y]
        ]);
    }

    public function obtenerOpcionesOrdenadas()
    {
        return $this->opcionesActivas()->orderBy('orden')->get();
    }

    public function obtenerOpcionPorLetra(string $letra): ?PanelDialogoOpcion
    {
        return $this->opcionesActivas()->where('letra', strtoupper($letra))->first();
    }

    public function obtenerDialogosSiguientes(): array
    {
        $dialogosSiguientes = [];
        
        foreach ($this->opcionesActivas as $opcion) {
            $conexion = $opcion->conexiones->first();
            if ($conexion && $conexion->dialogoDestino) {
                $dialogosSiguientes[] = [
                    'opcion' => $opcion,
                    'dialogo' => $conexion->dialogoDestino
                ];
            }
        }
        
        return $dialogosSiguientes;
    }

    public function obtenerRutaCompleta(): array
    {
        $ruta = [$this];
        $dialogosSiguientes = $this->obtenerDialogosSiguientes();
        
        foreach ($dialogosSiguientes as $siguiente) {
            $ruta = array_merge($ruta, $siguiente['dialogo']->obtenerRutaCompleta());
        }
        
        return array_unique($ruta, SORT_REGULAR);
    }

    public function validarEstructura(): array
    {
        $errores = [];

        // Validar contenido
        if (empty(trim($this->contenido))) {
            $errores[] = 'El contenido del diálogo no puede estar vacío';
        }

        // Validar opciones para diálogos de decisión
        if ($this->tipo === 'decision') {
            if ($this->opciones()->count() === 0) {
                $errores[] = 'Los diálogos de decisión deben tener al menos una opción';
            }
            
            if ($this->opciones()->count() > 3) {
                $errores[] = 'Los diálogos de decisión no pueden tener más de 3 opciones';
            }
        }

        // Validar que los diálogos automáticos no tengan opciones
        if ($this->tipo === 'automatico' && $this->opciones()->count() > 0) {
            $errores[] = 'Los diálogos automáticos no deben tener opciones';
        }

        // Validar posición
        if ($this->posicion && (!isset($this->posicion['x']) || !isset($this->posicion['y']))) {
            $errores[] = 'La posición debe tener coordenadas x e y válidas';
        }

        return $errores;
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'total_opciones' => $this->total_opciones,
            'total_conexiones_salientes' => $this->total_conexiones_salientes,
            'total_conexiones_entrantes' => $this->total_conexiones_entrantes,
            'total_decisiones' => $this->decisiones()->count(),
            'es_decision' => $this->es_decision,
            'es_automatico' => $this->es_automatico,
            'tiene_opciones' => $this->tiene_opciones
        ];
    }

    public function duplicar(): self
    {
        $nuevoDialogo = $this->replicate(['id', 'created_at', 'updated_at']);
        $nuevoDialogo->titulo = $this->titulo . ' (Copia)';
        $nuevoDialogo->es_inicial = false; // Las copias no son iniciales
        $nuevoDialogo->save();

        // Copiar opciones
        foreach ($this->opciones as $opcion) {
            $nuevaOpcion = $opcion->replicate(['id', 'dialogo_id', 'created_at', 'updated_at']);
            $nuevaOpcion->dialogo_id = $nuevoDialogo->id;
            $nuevaOpcion->save();
        }

        return $nuevoDialogo;
    }
}