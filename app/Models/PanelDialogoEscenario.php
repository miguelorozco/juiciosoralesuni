<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelDialogoEscenario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'panel_dialogo_escenarios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'estado',
        'publico',
        'configuracion',
        'creado_por'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'publico' => 'boolean',
    ];

    // Relaciones
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(PanelDialogoRol::class, 'escenario_id')->orderBy('orden');
    }

    public function rolesActivos(): HasMany
    {
        return $this->hasMany(PanelDialogoRol::class, 'escenario_id')->where('activo', true)->orderBy('orden');
    }

    public function flujos(): HasMany
    {
        return $this->hasMany(PanelDialogoFlujo::class, 'escenario_id')->orderBy('orden');
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(PanelDialogoSesion::class, 'escenario_id');
    }

    public function conexiones(): HasMany
    {
        return $this->hasMany(PanelDialogoConexion::class, 'escenario_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePublicos($query)
    {
        return $query->where('publico', true);
    }

    public function scopeDisponiblesParaUsuario($query, User $user)
    {
        return $query->where(function($q) use ($user) {
            $q->where('publico', true)
              ->orWhere('creado_por', $user->id)
              ->orWhereHas('sesiones', function($sq) use ($user) {
                  $sq->where('instructor_id', $user->id);
              });
        });
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Accessors
    public function getTotalRolesAttribute(): int
    {
        return $this->roles()->count();
    }

    public function getTotalFlujosAttribute(): int
    {
        return $this->flujos()->count();
    }

    public function getTotalDialogosAttribute(): int
    {
        return $this->flujos()->withCount('dialogos')->get()->sum('dialogos_count');
    }

    public function getTotalSesionesAttribute(): int
    {
        return $this->sesiones()->count();
    }

    public function getComplejidadAttribute(): string
    {
        $totalDialogos = $this->total_dialogos;
        
        if ($totalDialogos <= 5) return 'Baja';
        if ($totalDialogos <= 15) return 'Media';
        return 'Alta';
    }

    // Métodos
    public function puedeSerUsadoPor(User $user): bool
    {
        return $this->publico || 
               $this->creado_por === $user->id || 
               $this->sesiones()->where('instructor_id', $user->id)->exists();
    }

    public function puedeSerEditadoPor(User $user): bool
    {
        return $this->creado_por === $user->id || $user->tipo === 'admin';
    }

    public function activar(): void
    {
        $this->update(['estado' => 'activo']);
    }

    public function archivar(): void
    {
        $this->update(['estado' => 'archivado']);
    }

    public function obtenerEstructuraCompleta(): array
    {
        return [
            'escenario' => $this,
            'roles' => $this->rolesActivos()->with([
                'flujos.dialogos.opciones',
                'flujos.dialogos.conexionesSalientes'
            ])->get(),
            'conexiones' => $this->conexiones()->with(['dialogoOrigen', 'dialogoDestino', 'opcion'])->get(),
            'estadisticas' => [
                'total_roles' => $this->total_roles,
                'total_flujos' => $this->total_flujos,
                'total_dialogos' => $this->total_dialogos,
                'total_sesiones' => $this->total_sesiones,
                'complejidad' => $this->complejidad
            ]
        ];
    }

    public function crearCopia(string $nombre, int $creadoPor): self
    {
        $nuevoEscenario = $this->replicate(['id', 'created_at', 'updated_at']);
        $nuevoEscenario->nombre = $nombre;
        $nuevoEscenario->creado_por = $creadoPor;
        $nuevoEscenario->estado = 'borrador';
        $nuevoEscenario->save();

        // Copiar roles
        foreach ($this->roles as $rol) {
            $nuevoRol = $rol->replicate(['id', 'escenario_id', 'created_at', 'updated_at']);
            $nuevoRol->escenario_id = $nuevoEscenario->id;
            $nuevoRol->save();

            // Copiar flujos
            foreach ($rol->flujos as $flujo) {
                $nuevoFlujo = $flujo->replicate(['id', 'rol_id', 'created_at', 'updated_at']);
                $nuevoFlujo->rol_id = $nuevoRol->id;
                $nuevoFlujo->save();

                // Copiar diálogos
                foreach ($flujo->dialogos as $dialogo) {
                    $nuevoDialogo = $dialogo->replicate(['id', 'flujo_id', 'created_at', 'updated_at']);
                    $nuevoDialogo->flujo_id = $nuevoFlujo->id;
                    $nuevoDialogo->save();

                    // Copiar opciones
                    foreach ($dialogo->opciones as $opcion) {
                        $nuevaOpcion = $opcion->replicate(['id', 'dialogo_id', 'created_at', 'updated_at']);
                        $nuevaOpcion->dialogo_id = $nuevoDialogo->id;
                        $nuevaOpcion->save();
                    }
                }
            }
        }

        return $nuevoEscenario;
    }
}