<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el enum para incluir 'programada'
        DB::statement("ALTER TABLE sesiones_dialogos MODIFY COLUMN estado ENUM('programada', 'iniciado', 'en_curso', 'pausado', 'finalizado') DEFAULT 'programada'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum original
        DB::statement("ALTER TABLE sesiones_dialogos MODIFY COLUMN estado ENUM('iniciado', 'en_curso', 'pausado', 'finalizado') DEFAULT 'iniciado'");
    }
};