<?php

namespace Tests\Feature\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RolDisponible;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class RolDisponibleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario administrador para los tests
        $this->user = User::create([
            'name' => 'Admin',
            'apellido' => 'Sistema',
            'email' => 'admin@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'admin',
            'activo' => true
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function puede_listar_roles_disponibles()
    {
        // Crear algunos roles
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'nombre',
                            'descripcion',
                            'color',
                            'icono',
                            'activo',
                            'orden',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'total'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Roles obtenidos exitosamente'
            ]);

        $this->assertCount(2, $response->json('data.data'));
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/roles/activos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'descripcion',
                        'color',
                        'activo',
                        'orden'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Roles activos obtenidos exitosamente'
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Juez Activo', $response->json('data.0.nombre'));
    }

    /** @test */
    public function puede_crear_nuevo_rol()
    {
        $rolData = [
            'nombre' => 'Testigo',
            'descripcion' => 'Persona que testifica en el juicio',
            'color' => '#FF5733',
            'icono' => 'user',
            'activo' => true,
            'orden' => 3
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/roles', $rolData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nombre',
                    'descripcion',
                    'color',
                    'icono',
                    'activo',
                    'orden',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Rol creado exitosamente'
            ]);

        // Verificar que el rol se creó en la base de datos
        $this->assertDatabaseHas('roles_disponibles', [
            'nombre' => 'Testigo',
            'color' => '#FF5733',
            'activo' => true
        ]);
    }

    /** @test */
    public function no_puede_crear_rol_con_datos_invalidos()
    {
        $rolData = [
            'nombre' => 'A', // Nombre muy corto
            'descripcion' => 'Corta', // Descripción muy corta
            'color' => '#GGGGGG', // Color inválido
            'activo' => 'invalid' // Valor inválido
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/roles', $rolData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    }

    /** @test */
    public function puede_ver_rol_especifico()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/roles/' . $rol->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nombre',
                    'descripcion',
                    'color',
                    'activo',
                    'orden'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Rol obtenido exitosamente',
                'data' => [
                    'id' => $rol->id,
                    'nombre' => 'Juez'
                ]
            ]);
    }

    /** @test */
    public function puede_actualizar_rol()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $updateData = [
            'nombre' => 'Juez Principal',
            'descripcion' => 'Preside el juicio principal',
            'color' => '#FF5733',
            'activo' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/roles/' . $rol->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nombre',
                    'descripcion',
                    'color',
                    'activo',
                    'orden'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Rol actualizado exitosamente'
            ]);

        // Verificar que el rol se actualizó en la base de datos
        $this->assertDatabaseHas('roles_disponibles', [
            'id' => $rol->id,
            'nombre' => 'Juez Principal',
            'color' => '#FF5733',
            'activo' => false
        ]);
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/roles/' . $rol->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rol eliminado exitosamente'
            ]);

        // Verificar que el rol se eliminó de la base de datos
        $this->assertDatabaseMissing('roles_disponibles', [
            'id' => $rol->id
        ]);
    }

    /** @test */
    public function puede_cambiar_estado_activo_inactivo()
    {
        $rol = RolDisponible::create([
            'nombre' => 'Juez',
            'descripcion' => 'Preside el juicio',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/roles/' . $rol->id . '/toggle-activo');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nombre',
                    'activo'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Estado del rol actualizado exitosamente'
            ]);

        // Verificar que el estado cambió
        $this->assertDatabaseHas('roles_disponibles', [
            'id' => $rol->id,
            'activo' => false
        ]);
    }

    /** @test */
    public function puede_reordenar_roles()
    {
        $rol1 = RolDisponible::create([
            'nombre' => 'Primero',
            'descripcion' => 'Primer rol',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 1
        ]);

        $rol2 = RolDisponible::create([
            'nombre' => 'Segundo',
            'descripcion' => 'Segundo rol',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 2
        ]);

        $rol3 = RolDisponible::create([
            'nombre' => 'Tercero',
            'descripcion' => 'Tercer rol',
            'color' => '#8B4513',
            'activo' => true,
            'orden' => 3
        ]);

        $reorderData = [
            'roles' => [
                ['id' => $rol3->id, 'orden' => 1],
                ['id' => $rol1->id, 'orden' => 2],
                ['id' => $rol2->id, 'orden' => 3]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/roles/reordenar', $reorderData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Roles reordenados exitosamente'
            ]);

        // Verificar que el orden se actualizó
        $this->assertDatabaseHas('roles_disponibles', [
            'id' => $rol3->id,
            'orden' => 1
        ]);

        $this->assertDatabaseHas('roles_disponibles', [
            'id' => $rol1->id,
            'orden' => 2
        ]);

        $this->assertDatabaseHas('roles_disponibles', [
            'id' => $rol2->id,
            'orden' => 3
        ]);
    }

    /** @test */
    public function no_puede_acceder_sin_autenticacion()
    {
        $response = $this->getJson('/api/roles');

        $response->assertStatus(401);
    }

    /** @test */
    public function puede_filtrar_roles_por_estado_activo()
    {
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/roles?activo=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Juez Activo', $response->json('data.data.0.nombre'));
    }

    /** @test */
    public function puede_buscar_roles_por_nombre()
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/roles?tipo=Juez');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Juez', $response->json('data.data.0.nombre'));
    }

    /** @test */
    public function puede_paginar_resultados()
    {
        // Crear más roles de los que caben en una página
        for ($i = 1; $i <= 25; $i++) {
            RolDisponible::create([
                'nombre' => "Rol $i",
                'descripcion' => "Descripción del rol $i",
                'color' => '#8B4513',
                'activo' => true,
                'orden' => $i
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/roles?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data.data'));
        $this->assertEquals(25, $response->json('data.total'));
        $this->assertEquals(3, $response->json('data.last_page'));
    }
}