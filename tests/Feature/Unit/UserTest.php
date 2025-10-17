<?php

namespace Tests\Feature\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\PlantillaSesion;
use App\Models\SesionJuicio;
use App\Models\AsignacionRol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_crear_un_usuario()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Juan', $user->name);
        $this->assertEquals('Pérez', $user->apellido);
        $this->assertEquals('juan.perez@ejemplo.com', $user->email);
        $this->assertEquals('instructor', $user->tipo);
        $this->assertTrue($user->activo);
    }

    /** @test */
    public function puede_verificar_password()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertFalse(Hash::check('wrongpassword', $user->password));
    }

    /** @test */
    public function puede_cambiar_password()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $user->password = Hash::make('newpassword456');
        $user->save();

        $this->assertTrue(Hash::check('newpassword456', $user->fresh()->password));
        $this->assertFalse(Hash::check('password123', $user->fresh()->password));
    }

    /** @test */
    public function puede_cambiar_estado_activo()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $this->assertTrue($user->activo);

        $user->activo = false;
        $user->save();

        $this->assertFalse($user->fresh()->activo);
    }

    /** @test */
    public function puede_cambiar_tipo_de_usuario()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'alumno',
            'activo' => true
        ]);

        $this->assertEquals('alumno', $user->tipo);

        $user->tipo = 'instructor';
        $user->save();

        $this->assertEquals('instructor', $user->fresh()->tipo);
    }

    /** @test */
    public function tiene_relacion_con_plantillas_creadas()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $plantilla = PlantillaSesion::create([
            'nombre' => 'Plantilla Civil',
            'descripcion' => 'Para juicios civiles',
            'creado_por' => $user->id,
            'publica' => true
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->plantillasCreadas);
        $this->assertTrue($user->plantillasCreadas->contains($plantilla));
    }

    /** @test */
    public function tiene_relacion_con_sesiones_como_instructor()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $sesion = SesionJuicio::create([
            'nombre' => 'Sesión Civil 001',
            'descripcion' => 'Primera sesión civil',
            'instructor_id' => $user->id,
            'estado' => 'programada',
            'max_participantes' => 20
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->sesionesComoInstructor);
        $this->assertTrue($user->sesionesComoInstructor->contains($sesion));
    }

    /** @test */
    public function tiene_relacion_con_asignaciones()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'alumno',
            'activo' => true
        ]);

        $asignacion = AsignacionRol::create([
            'sesion_id' => 1,
            'usuario_id' => $user->id,
            'rol_id' => 1,
            'asignado_por' => 1,
            'confirmado' => false
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->asignaciones);
        $this->assertTrue($user->asignaciones->contains($asignacion));
    }

    /** @test */
    public function puede_obtener_nombre_completo()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $this->assertEquals('Juan Pérez', $user->name . ' ' . $user->apellido);
    }

    /** @test */
    public function valida_campos_requeridos()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);
    }

    /** @test */
    public function valida_unicidad_del_email()
    {
        User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => 'María',
            'apellido' => 'García',
            'email' => 'juan.perez@ejemplo.com', // Email duplicado
            'password' => Hash::make('password123'),
            'tipo' => 'alumno',
            'activo' => true
        ]);
    }

    /** @test */
    public function valida_tipo_de_usuario()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'tipo_invalido', // Tipo inválido
            'activo' => true
        ]);
    }

    /** @test */
    public function puede_buscar_por_tipo()
    {
        User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        User::create([
            'name' => 'María',
            'apellido' => 'García',
            'email' => 'maria.garcia@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'alumno',
            'activo' => true
        ]);

        $instructores = User::where('tipo', 'instructor')->get();

        $this->assertCount(1, $instructores);
        $this->assertEquals('Juan', $instructores->first()->name);
    }

    /** @test */
    public function puede_buscar_por_estado_activo()
    {
        User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        User::create([
            'name' => 'María',
            'apellido' => 'García',
            'email' => 'maria.garcia@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'alumno',
            'activo' => false
        ]);

        $usuariosActivos = User::where('activo', true)->get();

        $this->assertCount(1, $usuariosActivos);
        $this->assertEquals('Juan', $usuariosActivos->first()->name);
    }

    /** @test */
    public function puede_eliminar_usuario()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $userId = $user->id;
        $user->delete();

        $this->assertNull(User::find($userId));
    }
}