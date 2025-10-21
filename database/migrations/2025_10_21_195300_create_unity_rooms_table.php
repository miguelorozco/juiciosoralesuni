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
        Schema::create('unity_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_id')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->foreignId('sesion_juicio_id')->constrained('sesiones_juicios')->onDelete('cascade');
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->string('estado')->default('activa'); // activa, pausada, finalizada, cerrada
            $table->json('configuracion')->nullable(); // Configuración específica de Unity
            $table->json('audio_config')->nullable(); // Configuración de audio
            $table->json('participantes_activos')->nullable(); // Usuarios conectados actualmente
            $table->integer('max_participantes')->default(10);
            $table->integer('participantes_conectados')->default(0);
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->timestamp('ultima_actividad')->nullable();
            $table->timestamps();
            
            $table->index(['room_id', 'estado']);
            $table->index(['sesion_juicio_id', 'estado']);
            $table->index('ultima_actividad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unity_rooms');
    }
};