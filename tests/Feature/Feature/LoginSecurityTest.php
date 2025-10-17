<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        
        // Crear usuario de prueba
        User::create([
            'name' => 'Test',
            'apellido' => 'User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'tipo' => 'alumno',
            'activo' => true,
        ]);
    }

    /** @test */
    public function login_exitoso_registra_intento_exitoso()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'test@example.com',
            'successful' => true,
        ]);
    }

    /** @test */
    public function login_fallido_registra_intento_fallido()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'test@example.com',
            'successful' => false,
        ]);
    }

    /** @test */
    public function login_con_usuario_inexistente_registra_intento_fallido()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'nonexistent@example.com',
            'successful' => false,
        ]);
    }

    /** @test */
    public function bloquea_ip_despues_de_muchos_intentos_fallidos()
    {
        // Simular múltiples intentos fallidos desde la misma IP
        for ($i = 0; $i < 10; $i++) {
            LoginAttempt::recordAttempt('test@example.com', '192.168.1.1', 'Test Agent', false);
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Demasiados intentos fallidos desde esta dirección IP. Intenta más tarde.'
            ]);
    }

    /** @test */
    public function bloquea_email_despues_de_muchos_intentos_fallidos()
    {
        // Simular múltiples intentos fallidos para el mismo email
        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::recordAttempt('test@example.com', '192.168.1.' . $i, 'Test Agent', false);
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Demasiados intentos fallidos para esta cuenta. Intenta más tarde.'
            ]);
    }

    /** @test */
    public function rate_limiting_funciona_correctamente()
    {
        // Simular múltiples requests rápidos
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // El sexto intento debería ser bloqueado por rate limiting
        $response->assertStatus(429);
    }

    /** @test */
    public function login_exitoso_actualiza_ultimo_acceso()
    {
        $user = User::where('email', 'test@example.com')->first();
        $originalAccess = $user->ultimo_acceso;

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotEquals($originalAccess, $user->ultimo_acceso);
        $this->assertNotNull($user->ultimo_acceso);
    }
}
