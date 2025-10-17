<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use App\Models\ConfiguracionSistema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        
        // Crear configuración de registro bloqueado
        ConfiguracionSistema::create([
            'clave' => 'registro_usuarios_habilitado',
            'valor' => 'false',
            'descripcion' => 'Controla si los usuarios pueden registrarse',
            'tipo' => 'boolean',
        ]);

        ConfiguracionSistema::create([
            'clave' => 'mensaje_registro_bloqueado',
            'valor' => 'El registro está temporalmente deshabilitado.',
            'descripcion' => 'Mensaje de bloqueo',
            'tipo' => 'string',
        ]);
    }

    /** @test */
    public function usuario_no_puede_registrarse_cuando_esta_bloqueado()
    {
        $userData = [
            'name' => 'Test',
            'apellido' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tipo' => 'alumno'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registro'])
            ->assertJson([
                'errors' => [
                    'registro' => ['Esta deshabilitada la creación de cuentas por el momento']
                ]
            ]);
    }

    /** @test */
    public function usuario_puede_registrarse_cuando_esta_habilitado()
    {
        // Habilitar registro
        ConfiguracionSistema::where('clave', 'registro_usuarios_habilitado')
            ->update(['valor' => 'true']);

        $userData = [
            'name' => 'Test',
            'apellido' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tipo' => 'alumno'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario registrado exitosamente'
            ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /** @test */
    public function admin_puede_crear_usuario_aunque_registro_este_bloqueado()
    {
        // Crear usuario administrador
        $admin = User::create([
            'name' => 'Admin',
            'apellido' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'tipo' => 'admin',
            'activo' => true,
        ]);

        $token = auth()->login($admin);

        $userData = [
            'name' => 'Test',
            'apellido' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tipo' => 'alumno'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/create-user', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario creado exitosamente por administrador'
            ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /** @test */
    public function puede_obtener_estado_del_registro()
    {
        $response = $this->getJson('/api/auth/registration-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'registro_habilitado' => false,
                    'mensaje_bloqueo' => 'El registro está temporalmente deshabilitado.',
                ]
            ]);
    }

    /** @test */
    public function admin_puede_cambiar_estado_del_registro()
    {
        // Crear usuario administrador
        $admin = User::create([
            'name' => 'Admin',
            'apellido' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'tipo' => 'admin',
            'activo' => true,
        ]);

        $token = auth()->login($admin);

        // Cambiar estado a habilitado
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/toggle-registration');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'registro_habilitado' => true,
                    'mensaje' => 'Registro de usuarios habilitado'
                ]
            ]);

        // Verificar que se actualizó en la base de datos
        $config = ConfiguracionSistema::where('clave', 'registro_usuarios_habilitado')->first();
        $this->assertEquals('true', $config->valor);
    }

    /** @test */
    public function usuario_no_admin_no_puede_cambiar_estado_registro()
    {
        // Crear usuario no administrador
        $user = User::create([
            'name' => 'Regular',
            'apellido' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'tipo' => 'alumno',
            'activo' => true,
        ]);

        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/toggle-registration');

        $response->assertStatus(403);
    }

    /** @test */
    public function usuario_no_admin_no_puede_crear_usuarios()
    {
        // Crear usuario no administrador
        $user = User::create([
            'name' => 'Regular',
            'apellido' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'tipo' => 'instructor',
            'activo' => true,
        ]);

        $token = auth()->login($user);

        $userData = [
            'name' => 'Test',
            'apellido' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tipo' => 'alumno'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/create-user', $userData);

        $response->assertStatus(403);
    }
}
