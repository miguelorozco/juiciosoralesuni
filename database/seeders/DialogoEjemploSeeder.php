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
class DialogoEjemploSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Este seeder necesita actualización completa
        // Por ahora se marca como deprecated
        $this->command->warn('Este seeder está deprecated. Actualizar para usar modelos v2.');
    }
}
