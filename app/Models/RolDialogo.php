<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RolDialogo extends Model
{
    use HasFactory;

    protected $table = 'roles_dialogo';

    protected $fillable = [
        'dialogo_id',
        'nombre',
        'descripcion',
        'icono',
        'orden',
        'requerido',
        'activo',
        'configuracion',
    ];

    protected $casts = [
        'requerido' => 'boolean',
        'activo' => 'boolean',
        'configuracion' => 'array',
    ];

    /**
     * Relación con el diálogo
     */
    public function dialogo(): BelongsTo
    {
        return $this->belongsTo(Dialogo::class);
    }

    /**
     * Relación con las asignaciones de roles
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionRol::class, 'rol_dialogo_id');
    }

    /**
     * Scope para roles activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para roles requeridos
     */
    public function scopeRequeridos($query)
    {
        return $query->where('requerido', true);
    }

    /**
     * Scope para un diálogo específico
     */
    public function scopeParaDialogo($query, $dialogoId)
    {
        return $query->where('dialogo_id', $dialogoId);
    }
}