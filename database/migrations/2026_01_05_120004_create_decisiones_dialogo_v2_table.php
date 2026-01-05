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
        Schema::create('decisiones_dialogo_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_dialogo_id')->constrained('sesiones_dialogos_v2')->onDelete('cascade');
            $table->foreignId('nodo_dialogo_id')->nullable()->constrained('nodos_dialogo_v2')->onDelete('set null');
            $table->foreignId('respuesta_id')->nullable()->constrained('respuestas_dialogo_v2')->onDelete('set null');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('rol_id')->nullable()->constrained('roles_disponibles')->onDelete('set null');
            $table->text('texto_respuesta')->nullable();
            $table->integer('puntuacion_obtenida')->default(0);
            $table->integer('tiempo_respuesta')->nullable();
            $table->boolean('fue_opcion_por_defecto')->default(false);
            $table->boolean('usuario_registrado')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('sesion_dialogo_id', 'idx_sesion_dialogo');
            $table->index('usuario_id', 'idx_usuario_id');
            $table->index('nodo_dialogo_id', 'idx_nodo_dialogo');
            $table->index('respuesta_id', 'idx_respuesta');
            $table->index('usuario_registrado', 'idx_usuario_registrado');
            $table->index('created_at', 'idx_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decisiones_dialogo_v2');
    }
};
