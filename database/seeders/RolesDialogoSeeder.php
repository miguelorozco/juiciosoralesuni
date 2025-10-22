<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dialogo;
use App\Models\RolDialogo;

class RolesDialogoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el diálogo de juicio penal
        $dialogo = Dialogo::where('nombre', 'like', '%Robo a Tienda%')->first();
        
        if (!$dialogo) {
            $this->command->warn('No se encontró el diálogo de juicio penal');
            return;
        }

        $roles = [
            [
                'nombre' => 'Juez',
                'descripcion' => 'Magistrado que preside el juicio y toma las decisiones finales',
                'icono' => 'gavel',
                'orden' => 1,
                'requerido' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Fiscal',
                'descripcion' => 'Representante del Ministerio Público que acusa al imputado',
                'icono' => 'briefcase',
                'orden' => 2,
                'requerido' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Abogado Defensor',
                'descripcion' => 'Defensor del imputado que busca su absolución o reducción de pena',
                'icono' => 'shield-check',
                'orden' => 3,
                'requerido' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Imputado',
                'descripcion' => 'Persona acusada del delito de robo',
                'icono' => 'person-badge',
                'orden' => 4,
                'requerido' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Víctima',
                'descripcion' => 'Dueño de la tienda que fue robada',
                'icono' => 'person-heart',
                'orden' => 5,
                'requerido' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Testigo',
                'descripcion' => 'Testigo presencial del robo',
                'icono' => 'eye',
                'orden' => 6,
                'requerido' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Perito',
                'descripcion' => 'Especialista que analiza las pruebas técnicas',
                'icono' => 'microscope',
                'orden' => 7,
                'requerido' => false,
                'activo' => true,
            ],
            [
                'nombre' => 'Secretario',
                'descripcion' => 'Funcionario que registra las actas del juicio',
                'icono' => 'file-text',
                'orden' => 8,
                'requerido' => false,
                'activo' => true,
            ],
            [
                'nombre' => 'Policía Investigador',
                'descripcion' => 'Agente que realizó la investigación del caso',
                'icono' => 'badge',
                'orden' => 9,
                'requerido' => false,
                'activo' => true,
            ],
            [
                'nombre' => 'Público',
                'descripcion' => 'Observador del juicio (puede ser familiar o ciudadano)',
                'icono' => 'people',
                'orden' => 10,
                'requerido' => false,
                'activo' => true,
            ],
        ];

        foreach ($roles as $rolData) {
            RolDialogo::updateOrCreate(
                [
                    'dialogo_id' => $dialogo->id,
                    'nombre' => $rolData['nombre'],
                ],
                array_merge($rolData, ['dialogo_id' => $dialogo->id])
            );
        }

        $this->command->info('Roles del diálogo creados exitosamente: ' . count($roles) . ' roles');
    }
}