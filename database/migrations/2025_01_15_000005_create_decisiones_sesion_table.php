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
        Schema::create('decisiones_sesion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones_juicios')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('rol_id')->constrained('roles_disponibles');
            $table->foreignId('nodo_dialogo_id')->constrained('nodos_dialogo');
            $table->foreignId('respuesta_id')->nullable()->constrained('respuestas_dialogo');
            $table->text('decision_texto')->nullable(); // Texto de la decisión tomada
            $table->json('metadata')->nullable(); // Datos adicionales de la decisión
            $table->integer('tiempo_respuesta')->nullable(); // Tiempo en segundos para responder
            $table->timestamp('fecha_decision');
            $table->timestamps();
            
            $table->index('sesion_id');
            $table->index('usuario_id');
            $table->index('rol_id');
            $table->index('nodo_dialogo_id');
            $table->index('fecha_decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decisiones_sesion');
    }
};
