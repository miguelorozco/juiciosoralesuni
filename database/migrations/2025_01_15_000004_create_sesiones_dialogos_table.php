<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sesiones_dialogos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sesion_id');
            $table->unsignedBigInteger('dialogo_id');
            $table->unsignedBigInteger('nodo_actual_id')->nullable();
            $table->enum('estado', ['iniciado', 'en_curso', 'pausado', 'finalizado'])->default('iniciado');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->json('configuracion')->nullable(); // Configuración específica de la sesión
            $table->json('variables')->nullable(); // Variables de estado del diálogo
            $table->timestamps();
            
            $table->index('sesion_id');
            $table->index('dialogo_id');
            $table->index('estado');
            $table->unique(['sesion_id', 'dialogo_id']); // Una sesión puede tener un diálogo activo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_dialogos');
    }
};
