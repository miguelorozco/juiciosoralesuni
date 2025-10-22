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
            $table->unsignedBigInteger('sesion_id');
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('nodo_dialogo_id');
            $table->unsignedBigInteger('respuesta_id')->nullable();
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
