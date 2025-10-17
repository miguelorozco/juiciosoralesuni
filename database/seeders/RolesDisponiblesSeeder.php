<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesDisponiblesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Juez', 'descripcion' => 'Preside el juicio y toma decisiones', 'color' => '#8B4513', 'orden' => 1],
            ['nombre' => 'Fiscal', 'descripcion' => 'Representa al Ministerio Público', 'color' => '#DC143C', 'orden' => 2],
            ['nombre' => 'Defensa', 'descripcion' => 'Abogado defensor del acusado', 'color' => '#4169E1', 'orden' => 3],
            ['nombre' => 'Testigo1', 'descripcion' => 'Primer testigo de cargo', 'color' => '#32CD32', 'orden' => 4],
            ['nombre' => 'Testigo2', 'descripcion' => 'Segundo testigo de cargo', 'color' => '#32CD32', 'orden' => 5],
            ['nombre' => 'Policía1', 'descripcion' => 'Primer oficial de policía', 'color' => '#000080', 'orden' => 6],
            ['nombre' => 'Policía2', 'descripcion' => 'Segundo oficial de policía', 'color' => '#000080', 'orden' => 7],
            ['nombre' => 'Psicólogo', 'descripcion' => 'Perito psicológico', 'color' => '#9370DB', 'orden' => 8],
            ['nombre' => 'Acusado', 'descripcion' => 'Persona acusada del delito', 'color' => '#FF6347', 'orden' => 9],
            ['nombre' => 'Secretario', 'descripcion' => 'Secretario de la audiencia', 'color' => '#696969', 'orden' => 10],
            ['nombre' => 'Abogado1', 'descripcion' => 'Primer abogado adicional', 'color' => '#4169E1', 'orden' => 11],
            ['nombre' => 'Abogado2', 'descripcion' => 'Segundo abogado adicional', 'color' => '#4169E1', 'orden' => 12],
            ['nombre' => 'Perito1', 'descripcion' => 'Primer perito técnico', 'color' => '#9370DB', 'orden' => 13],
            ['nombre' => 'Perito2', 'descripcion' => 'Segundo perito técnico', 'color' => '#9370DB', 'orden' => 14],
            ['nombre' => 'Víctima', 'descripcion' => 'Víctima del delito', 'color' => '#FF1493', 'orden' => 15],
            ['nombre' => 'Acusador', 'descripcion' => 'Persona que acusa', 'color' => '#DC143C', 'orden' => 16],
            ['nombre' => 'Periodista', 'descripcion' => 'Reportero de medios', 'color' => '#FF8C00', 'orden' => 17],
            ['nombre' => 'Público1', 'descripcion' => 'Primer miembro del público', 'color' => '#808080', 'orden' => 18],
            ['nombre' => 'Público2', 'descripcion' => 'Segundo miembro del público', 'color' => '#808080', 'orden' => 19],
            ['nombre' => 'Observador', 'descripcion' => 'Observador neutral', 'color' => '#708090', 'orden' => 20],
        ];

        foreach ($roles as $rol) {
            DB::table('roles_disponibles')->insert(array_merge($rol, [
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}