<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DialogoV2 as Dialogo;
use App\Models\NodoDialogoV2 as NodoDialogo;
use App\Models\RespuestaDialogoV2 as RespuestaDialogo;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated Este seeder usa modelos antiguos.
 * Se mantiene para referencia histórica.
 * TODO: Actualizar completamente para usar modelos v2 con todas las nuevas características.
 */
class DialogoJuicioPenalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Crear diálogo
            $dialogo = Dialogo::create([
                'nombre' => 'Juicio Penal - Robo con Violencia',
                'descripcion' => 'Diálogo ramificado para simular un juicio penal sobre un caso de robo con violencia',
                'creado_por' => 1, // Asumir que existe un usuario admin con ID 1
                'publico' => true,
                'estado' => 'activo',
                'version' => '1.0.0',
                'configuracion' => [
                    'tiempo_maximo' => 120,
                    'permite_pausa' => true,
                ],
            ]);

            // Crear nodos
            $nodos = [];

            // Nodo inicial
            $nodos['inicio'] = NodoDialogo::create([
                'dialogo_id' => $dialogo->id,
                'titulo' => 'Inicio del Juicio',
                'contenido' => 'El juez da inicio al juicio penal por robo con violencia.',
                'tipo' => 'inicio',
                'posicion_x' => 0,
                'posicion_y' => 0,
                'es_inicial' => true,
                'es_final' => false,
                'orden' => 1,
                'activo' => true,
            ]);

            // Continuar con más nodos usando el mismo patrón...
            // NOTA: Este seeder necesita actualización completa para usar todas las características v2
            // como conversant_id, menu_text, condiciones, consecuencias, etc.

            $this->command->info("Diálogo '{$dialogo->nombre}' creado con ID: {$dialogo->id}");
        });
    }
}
