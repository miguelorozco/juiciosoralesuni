<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class EstudiantesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array de 10 estudiantes con credenciales inventadas
        $estudiantes = [
            [
                'name' => 'Ana',
                'apellido' => 'García',
                'email' => 'ana.garcia@estudiante.com',
                'password' => 'Ana2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'Carlos',
                'apellido' => 'Rodríguez',
                'email' => 'carlos.rodriguez@estudiante.com',
                'password' => 'Carlos2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'María',
                'apellido' => 'López',
                'email' => 'maria.lopez@estudiante.com',
                'password' => 'Maria2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'José',
                'apellido' => 'Martínez',
                'email' => 'jose.martinez@estudiante.com',
                'password' => 'Jose2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'Laura',
                'apellido' => 'Hernández',
                'email' => 'laura.hernandez@estudiante.com',
                'password' => 'Laura2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'Diego',
                'apellido' => 'González',
                'email' => 'diego.gonzalez@estudiante.com',
                'password' => 'Diego2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'Sofía',
                'apellido' => 'Pérez',
                'email' => 'sofia.perez@estudiante.com',
                'password' => 'Sofia2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'Andrés',
                'apellido' => 'Sánchez',
                'email' => 'andres.sanchez@estudiante.com',
                'password' => 'Andres2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'Valentina',
                'apellido' => 'Ramírez',
                'email' => 'valentina.ramirez@estudiante.com',
                'password' => 'Valentina2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
            [
                'name' => 'Sebastián',
                'apellido' => 'Cruz',
                'email' => 'sebastian.cruz@estudiante.com',
                'password' => 'Sebastian2024!',
                'tipo' => 'alumno',
                'activo' => true,
            ],
        ];

        // Obtener el ID del administrador para asignar como creado_por
        $admin = User::where('tipo', 'admin')->first();
        $creadoPor = $admin ? $admin->id : null;

        // Crear cada estudiante
        foreach ($estudiantes as $estudiante) {
            User::updateOrCreate(
                ['email' => $estudiante['email']],
                [
                    'name' => $estudiante['name'],
                    'apellido' => $estudiante['apellido'],
                    'email' => $estudiante['email'],
                    'password' => $estudiante['password'],
                    'tipo' => $estudiante['tipo'],
                    'activo' => $estudiante['activo'],
                    'email_verified_at' => now(),
                    'creado_por' => $creadoPor,
                    'ultimo_acceso' => null,
                    'configuracion' => json_encode([
                        'theme' => 'light',
                        'notifications' => true,
                        'language' => 'es',
                        'unity_info' => [
                            'last_login' => null,
                            'preferred_role' => null,
                            'session_history' => []
                        ]
                    ]),
                ]
            );

            $this->command->info("✅ Estudiante creado: {$estudiante['name']} {$estudiante['apellido']} - {$estudiante['email']}");
        }

        $this->command->info("🎓 Se han creado 10 estudiantes exitosamente!");
        $this->command->info("📧 Emails: ana.garcia@estudiante.com, carlos.rodriguez@estudiante.com, etc.");
        $this->command->info("🔑 Contraseñas: Ana2024!, Carlos2024!, Maria2024!, etc.");
    }
}
