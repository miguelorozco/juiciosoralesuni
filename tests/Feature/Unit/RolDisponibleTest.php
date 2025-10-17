<?php

namespace Tests\Feature\Unit;

use Tests\TestCase;
use App\Models\RolDisponible;
use App\Models\PlantillaAsignacion;
use App\Models\AsignacionRol;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolDisponibleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_crear_un_rol_disponible()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio y toma decisiones',
            'color' => '#8B4513',
            'icono' => 'gavel',
            'activo' => true,
            'orden' => 1
        ]);

        $this->assertInstanceOf(RolDisponible::class, $rol);
        $this->assertEquals('Juez', $rol->nombre);
        $this->assertEquals('#8B4513', $rol->color);
        $this->assertTrue($rol->activo);
        $this->assertEquals(1, $rol->orden);
    }

    /** @test */
    public function puede_obtener_roles_activos()
    {
        // Crear roles activos e inactivos
        RolDisponible::create([
            'nombre' => 'Juez Activo',
            'descripcion' => 'Rol activo',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        RolDisponible::create([
            'nombre' => 'Juez Inactivo',
            'descripcion' => 'Rol inactivo',
            'color' => '#8B4513',
            'activo' => false,
            'orden' => 2
        ]);

        $rolesActivos = RolDisponible::activos()->get();

        $this->assertCount(1, $rolesActivos);
        $this->assertEquals('Juez Activo', $rolesActivos->first()->nombre);
    }

    /** @test */
    public function tiene_relacion_con_plantilla_asignaciones()
    {
        // Crear usuario y plantilla primero
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'apellido' => 'Test',
            'email' => 'test@ejemplo.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $plantilla = \App\Models\PlantillaSesion::create([
            'nombre' => 'Plantilla Test',
            'descripcion' => 'Plantilla de prueba',
            'creado_por' => $user->id,
            'publica' => true
        ]);

        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $plantillaAsignacion = PlantillaAsignacion::create([
            'plantilla_id' => $plantilla->id,
            'rol_id' => $rol->id,
            'usuario_id' => $user->id,
            'orden' => 1
        ]);

        $rol->load('asignacionesPlantillas');
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $rol->asignacionesPlantillas);
        $this->assertTrue($rol->asignacionesPlantillas->contains($plantillaAsignacion));
    }

    /** @test */
    public function tiene_relacion_con_asignaciones_roles()
    {
        // Crear usuario y sesión primero
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'apellido' => 'Test',
            'email' => 'test2@ejemplo.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $sesion = \App\Models\SesionJuicio::create([
            'nombre' => 'Sesión Test',
            'descripcion' => 'Sesión de prueba',
            'instructor_id' => $user->id,
            'estado' => 'programada',
            'max_participantes' => 20
        ]);

        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $asignacionRol = AsignacionRol::create([
            'sesion_id' => $sesion->id,
            'usuario_id' => $user->id,
            'rol_id' => $rol->id,
            'asignado_por' => $user->id,
            'confirmado' => false
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $rol->asignacionesRoles);
        $this->assertTrue($rol->asignacionesRoles->contains($asignacionRol));
    }

    /** @test */
    public function puede_cambiar_estado_activo()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $this->assertTrue($rol->activo);

        $rol->activo = false;
        $rol->save();

        $this->assertFalse($rol->fresh()->activo);
    }

    /** @test */
    public function puede_actualizar_orden()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $this->assertEquals(1, $rol->orden);

        $rol->orden = 5;
        $rol->save();

        $this->assertEquals(5, $rol->fresh()->orden);
    }

    /** @test */
    public function puede_eliminar_rol()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $rolId = $rol->id;
        $rol->delete();

        $this->assertNull(RolDisponible::find($rolId));
    }

    /** @test */
    public function valida_campos_requeridos()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        RolDisponible::create([
            'descripcion' => 'Sin nombre',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);
    }

    /** @test */
    public function valida_unicidad_del_nombre()
    {
        RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Primer juez',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        RolDisponible::create([
            'nombre' => 'Juez', // Nombre duplicado
            'descripcion' => 'Segundo juez',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 2
        ]);
    }

    /** @test */
    public function puede_buscar_por_nombre()
    {
        RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        RolDisponible::create([
            'nombre' => 'Abogado',
            'descripcion' => 'Representa a las partes',
            'color' => '#0000FF',
            'activo' => true,
            'orden' => 2
        ]);

        $roles = RolDisponible::where('nombre', 'like', '%Juez%')->get();

        $this->assertCount(1, $roles);
        $this->assertEquals('Juez', $roles->first()->nombre);
    }

    /** @test */
    public function puede_ordenar_por_orden()
    {
        RolDisponible::create([
            'nombre' => 'Tercero',
            'descripcion' => 'Tercer rol',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 3
        ]);

        RolDisponible::create([
            'nombre' => 'Primero',
            'descripcion' => 'Primer rol',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        RolDisponible::create([
            'nombre' => 'Segundo',
            'descripcion' => 'Segundo rol',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 2
        ]);

        $roles = RolDisponible::orderBy('orden')->get();

        $this->assertEquals('Primero', $roles->first()->nombre);
        $this->assertEquals('Segundo', $roles->skip(1)->first()->nombre);
        $this->assertEquals('Tercero', $roles->last()->nombre);
    }
}