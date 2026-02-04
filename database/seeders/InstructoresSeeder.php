<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class InstructoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array de instructores con credenciales inventadas
        $instructores = [
            [
                'name' => 'Dr. Patricia',
                'apellido' => 'Mendoza',
                'email' => 'patricia.mendoza@instructor.com',
                'password' => 'Patricia2024!',
                'tipo' => 'instructor',
                'activo' => true,
            ],
            [
                'name' => 'Prof. Roberto',
                'apellido' => 'Silva',
                'email' => 'roberto.silva@instructor.com',
                'password' => 'Roberto2024!',
                'tipo' => 'instructor',
                'activo' => true,
            ],
            [
                'name' => 'Dra. Carmen',
                'apellido' => 'Vargas',
                'email' => 'carmen.vargas@instructor.com',
                'password' => 'Carmen2024!',
                'tipo' => 'instructor',
                'activo' => true,
            ],
            [
                'name' => 'Prof. Alejandro',
                'apellido' => 'Morales',
                'email' => 'alejandro.morales@instructor.com',
                'password' => 'Alejandro2024!',
                'tipo' => 'instructor',
                'activo' => true,
            ],
            [
                'name' => 'Dra. Isabel',
                'apellido' => 'Jiménez',
                'email' => 'isabel.jimenez@instructor.com',
                'password' => 'Isabel2024!',
                'tipo' => 'instructor',
                'activo' => true,
            ],
        ];

        // Obtener el ID del administrador para asignar como creado_por
        $admin = User::where('tipo', 'admin')->first();
        $creadoPor = $admin ? $admin->id : null;

        // Crear cada instructor
        foreach ($instructores as $instructor) {
            User::updateOrCreate(
                ['email' => $instructor['email']],
                [
                    'name' => $instructor['name'],
                    'apellido' => $instructor['apellido'],
                    'email' => $instructor['email'],
                    'password' => $instructor['password'],
                    'tipo' => $instructor['tipo'],
                    'activo' => $instructor['activo'],
                    'email_verified_at' => now(),
                    'creado_por' => $creadoPor,
                    'ultimo_acceso' => null,
                    'configuracion' => json_encode([
                        'theme' => 'light',
                        'notifications' => true,
                        'language' => 'es',
                        'instructor_preferences' => [
                            'default_session_duration' => 60,
                            'auto_assign_roles' => true,
                            'allow_student_role_changes' => false,
                            'session_recording' => true
                        ],
                        'unity_info' => [
                            'last_login' => null,
                            'preferred_role' => 'instructor',
                            'session_history' => []
                        ]
                    ]),
                ]
            );

            $this->command->info("✅ Instructor creado: {$instructor['name']} {$instructor['apellido']} - {$instructor['email']}");
        }

        $this->command->info("👨‍🏫 Se han creado 5 instructores exitosamente!");
        $this->command->info("📧 Emails: patricia.mendoza@instructor.com, roberto.silva@instructor.com, etc.");
        $this->command->info("🔑 Contraseñas: Patricia2024!, Roberto2024!, Carmen2024!, etc.");
    }
}
