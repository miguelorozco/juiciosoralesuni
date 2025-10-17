<?php

namespace Tests\Feature\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function usuario_puede_hacer_login_con_credenciales_validas()
    {
        // Crear usuario
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        // Hacer login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'juan.perez@ejemplo.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'apellido',
                        'email',
                        'tipo',
                        'activo'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login exitoso'
            ]);

        $this->assertNotNull($response->json('data.access_token'));
    }

    /** @test */
    public function usuario_no_puede_hacer_login_con_credenciales_invalidas()
    {
        // Crear usuario
        User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        // Intentar login con credenciales incorrectas
        $response = $this->postJson('/api/auth/login', [
            'email' => 'juan.perez@ejemplo.com',
            'password' => 'password_incorrecto'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ]);
    }

    /** @test */
    public function usuario_no_puede_hacer_login_con_email_inexistente()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'usuario.inexistente@ejemplo.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ]);
    }

    /** @test */
    public function usuario_puede_registrarse_con_datos_validos()
    {
        $userData = [
            'name' => 'María',
            'apellido' => 'García',
            'email' => 'maria.garcia@ejemplo.com',
            'password' => 'password123',
            'tipo' => 'alumno'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'apellido',
                    'email',
                    'tipo',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Usuario registrado exitosamente'
            ]);

        // Verificar que el usuario se creó en la base de datos
        $this->assertDatabaseHas('users', [
            'email' => 'maria.garcia@ejemplo.com',
            'tipo' => 'alumno'
        ]);
    }

    /** @test */
    public function usuario_no_puede_registrarse_con_datos_invalidos()
    {
        $userData = [
            'name' => 'M', // Nombre muy corto
            'apellido' => 'García',
            'email' => 'email_invalido', // Email inválido
            'password' => '123', // Password muy corto
            'tipo' => 'tipo_invalido' // Tipo inválido
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Datos de validación incorrectos'
            ]);
    }

    /** @test */
    public function usuario_no_puede_registrarse_con_email_duplicado()
    {
        // Crear usuario existente
        User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $userData = [
            'name' => 'María',
            'apellido' => 'García',
            'email' => 'juan.perez@ejemplo.com', // Email duplicado
            'password' => 'password123',
            'tipo' => 'alumno'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Datos de validación incorrectos'
            ]);
    }

    /** @test */
    public function usuario_autenticado_puede_obtener_su_perfil()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'apellido',
                    'email',
                    'tipo',
                    'activo'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Perfil obtenido exitosamente'
            ]);
    }

    /** @test */
    public function usuario_no_autenticado_no_puede_obtener_perfil()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function usuario_autenticado_puede_renovar_token()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Token renovado exitosamente'
            ]);

        // Verificar que el token es diferente
        $this->assertNotEquals($token, $response->json('data.access_token'));
    }

    /** @test */
    public function usuario_autenticado_puede_hacer_logout()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);
    }

    /** @test */
    public function usuario_autenticado_puede_actualizar_su_perfil()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $updateData = [
            'name' => 'Juan Carlos',
            'apellido' => 'Pérez García',
            'email' => 'juan.carlos@ejemplo.com'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'apellido',
                    'email',
                    'tipo',
                    'activo'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente'
            ]);

        // Verificar que los datos se actualizaron en la base de datos
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Juan Carlos',
            'apellido' => 'Pérez García',
            'email' => 'juan.carlos@ejemplo.com'
        ]);
    }

    /** @test */
    public function usuario_autenticado_puede_cambiar_password()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $updateData = [
            'password' => 'nuevapassword456'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente'
            ]);

        // Verificar que el password se actualizó
        $user->refresh();
        $this->assertTrue(Hash::check('nuevapassword456', $user->password));
    }

    /** @test */
    public function usuario_no_puede_actualizar_perfil_con_datos_invalidos()
    {
        $user = User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password123'),
            'tipo' => 'instructor',
            'activo' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $updateData = [
            'name' => 'J', // Nombre muy corto
            'email' => 'email_invalido' // Email inválido
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Datos de validación incorrectos'
            ]);
    }
}