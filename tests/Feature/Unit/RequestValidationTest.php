<?php

namespace Tests\Feature\Unit;

use Tests\TestCase;
use App\Http\Requests\StoreRolDisponibleRequest;
use App\Http\Requests\UpdateRolDisponibleRequest;
use App\Http\Requests\StorePlantillaSesionRequest;
use App\Http\Requests\AgregarRolPlantillaRequest;
use App\Http\Requests\ReordenarRolesRequest;
use App\Models\User;
use App\Models\RolDisponible;
use App\Models\PlantillaSesion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::create([
            'name' => 'Admin',
            'apellido' => 'Sistema',
            'email' => 'admin@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'admin',
            'activo' => true
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function store_rol_disponible_request_valida_datos_correctos()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio y toma decisiones',
            'color' => '#8B4513',
            'icono' => 'gavel',
            'activo' => true,
            'orden' => 1
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function store_rol_disponible_request_valida_nombre_requerido()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('nombre', $validator->errors()->toArray());
    }

    /** @test */
    public function store_rol_disponible_request_valida_nombre_minimo_caracteres()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'A', // Muy corto
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('nombre', $validator->errors()->toArray());
    }

    /** @test */
    public function store_rol_disponible_request_valida_color_hexadecimal()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#GGGGGG', // Color inválido
            'activo' => true,
            'orden' => 1
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
    }

    /** @test */
    public function store_rol_disponible_request_valida_activo_boolean()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => 'invalid', // No es boolean
            'orden' => 1
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('activo', $validator->errors()->toArray());
    }

    /** @test */
    public function store_rol_disponible_request_valida_orden_minimo()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => -1 // Orden negativo
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('orden', $validator->errors()->toArray());
    }

    /** @test */
    public function store_plantilla_sesion_request_valida_datos_correctos()
    {
        $request = new StorePlantillaSesionRequest();
        
        $data = [
            'nombre' => 'Plantilla Civil',
            'descripcion' => 'Plantilla para juicios civiles',
            'publica' => true,
            'configuracion' => [
                'duracion_maxima' => 120,
                'permite_grabacion' => true
            ],
            'roles' => [
                ['rol_id' => 1, 'orden' => 1],
                ['rol_id' => 2, 'orden' => 2]
            ]
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function store_plantilla_sesion_request_valida_nombre_requerido()
    {
        $request = new StorePlantillaSesionRequest();
        
        $data = [
            'descripcion' => 'Plantilla para juicios civiles',
            'publica' => true
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('nombre', $validator->errors()->toArray());
    }

    /** @test */
    public function store_plantilla_sesion_request_valida_maximo_roles()
    {
        $request = new StorePlantillaSesionRequest();
        
        $roles = [];
        for ($i = 1; $i <= 21; $i++) { // Más de 20 roles
            $roles[] = ['rol_id' => $i, 'orden' => $i];
        }

        $data = [
            'nombre' => 'Plantilla Civil',
            'descripcion' => 'Plantilla para juicios civiles',
            'publica' => true,
            'roles' => $roles
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('roles', $validator->errors()->toArray());
    }

    /** @test */
    public function agregar_rol_plantilla_request_valida_datos_correctos()
    {
        $request = new AgregarRolPlantillaRequest();
        
        $data = [
            'rol_id' => 1,
            'usuario_id' => 2,
            'orden' => 3
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function agregar_rol_plantilla_request_valida_rol_id_requerido()
    {
        $request = new AgregarRolPlantillaRequest();
        
        $data = [
            'usuario_id' => 2,
            'orden' => 3
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rol_id', $validator->errors()->toArray());
    }

    /** @test */
    public function reordenar_roles_request_valida_datos_correctos()
    {
        $request = new ReordenarRolesRequest();
        
        $data = [
            'roles' => [
                ['id' => 1, 'orden' => 1],
                ['id' => 2, 'orden' => 2],
                ['id' => 3, 'orden' => 3]
            ]
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function reordenar_roles_request_valida_roles_requerido()
    {
        $request = new ReordenarRolesRequest();
        
        $data = [];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('roles', $validator->errors()->toArray());
    }

    /** @test */
    public function reordenar_roles_request_valida_minimo_un_rol()
    {
        $request = new ReordenarRolesRequest();
        
        $data = [
            'roles' => []
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('roles', $validator->errors()->toArray());
    }

    /** @test */
    public function reordenar_roles_request_valida_orden_minimo()
    {
        $request = new ReordenarRolesRequest();
        
        $data = [
            'roles' => [
                ['id' => 1, 'orden' => 0], // Orden muy bajo
                ['id' => 2, 'orden' => 2]
            ]
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('roles.0.orden', $validator->errors()->toArray());
    }

    /** @test */
    public function reordenar_roles_request_valida_orden_maximo()
    {
        $request = new ReordenarRolesRequest();
        
        $data = [
            'roles' => [
                ['id' => 1, 'orden' => 1],
                ['id' => 2, 'orden' => 1000] // Orden muy alto
            ]
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('roles.1.orden', $validator->errors()->toArray());
    }

    /** @test */
    public function store_rol_disponible_request_valida_unicidad_nombre()
    {
        // Crear rol existente
        RolDisponible::create([
            'nombre' => 'Juez Existente',
            'descripcion' => 'Rol existente',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez Existente', // Nombre duplicado
            'descripcion' => 'Nuevo rol',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 2
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('nombre', $validator->errors()->toArray());
    }

    /** @test */
    public function update_rol_disponible_request_valida_datos_correctos()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez Original',
            'descripcion' => 'Rol original',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $request = new UpdateRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez Actualizado',
            'descripcion' => 'Rol actualizado',
            'color' => '#FF5733',
            'activo' => false,
            'orden' => 2
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function update_rol_disponible_request_permite_mismo_nombre()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez Original',
            'descripcion' => 'Rol original',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $request = new UpdateRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez Original', // Mismo nombre
            'descripcion' => 'Rol actualizado',
            'color' => '#FF5733',
            'activo' => false,
            'orden' => 2
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function store_rol_disponible_request_valida_descripcion_minimo_caracteres()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez',
            'descripcion' => 'Corta', // Muy corta
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('descripcion', $validator->errors()->toArray());
    }

    /** @test */
    public function store_rol_disponible_request_valida_icono_formato()
    {
        $request = new StoreRolDisponibleRequest();
        
        $data = [
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'icono' => 'invalid@icon!', // Formato inválido
            'activo' => true,
            'orden' => 1
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('icono', $validator->errors()->toArray());
    }
}