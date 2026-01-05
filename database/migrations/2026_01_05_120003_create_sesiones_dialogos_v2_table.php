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
        Schema::create('sesiones_dialogos_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones_juicios')->onDelete('cascade');
            $table->foreignId('dialogo_id')->constrained('dialogos_v2')->onDelete('cascade');
            $table->foreignId('nodo_actual_id')->nullable()->constrained('nodos_dialogo_v2')->onDelete('set null');
            $table->enum('estado', ['iniciado', 'en_curso', 'pausado', 'finalizado'])->default('iniciado');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->json('variables')->nullable();
            $table->json('configuracion')->nullable();
            $table->json('historial_nodos')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('sesion_id', 'idx_sesion_id');
            $table->index('dialogo_id', 'idx_dialogo_id');
            $table->index('estado', 'idx_estado');
            $table->index('nodo_actual_id', 'idx_nodo_actual');
            
            // Unique constraint: una sesión solo puede tener un diálogo activo
            $table->unique(['sesion_id', 'dialogo_id'], 'unique_sesion_dialogo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_dialogos_v2');
    }
};
