<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Actualiza el enum 'tipo' para incluir 'agrupacion' (equivalente a isGroup en Pixel Crushers)
     * MySQL requiere modificar la columna directamente
     */
    public function up(): void
    {
        // MySQL no permite modificar ENUM directamente con Laravel Schema
        // Necesitamos usar DB::statement
        DB::statement("ALTER TABLE nodos_dialogo_v2 MODIFY COLUMN tipo ENUM('inicio', 'desarrollo', 'decision', 'final', 'agrupacion') DEFAULT 'desarrollo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a enum original
        DB::statement("ALTER TABLE nodos_dialogo_v2 MODIFY COLUMN tipo ENUM('inicio', 'desarrollo', 'decision', 'final') DEFAULT 'desarrollo'");
    }
};
