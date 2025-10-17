<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'apellido',
        'email',
        'password',
        'tipo',
        'activo',
        'creado_por',
        'configuracion',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'configuracion' => 'array',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'tipo' => $this->tipo,
            'activo' => $this->activo,
        ];
    }

    /**
     * Relaciones
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function usuariosCreados()
    {
        return $this->hasMany(User::class, 'creado_por');
    }

    public function sesionesComoInstructor()
    {
        return $this->hasMany(SesionJuicio::class, 'instructor_id');
    }

    public function asignacionesRoles()
    {
        return $this->hasMany(AsignacionRol::class, 'usuario_id');
    }

    public function plantillasCreadas()
    {
        return $this->hasMany(PlantillaSesion::class, 'creado_por');
    }
}
