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
        Schema::create('unity_room_events', function (Blueprint $table) {
            $table->id();
            $table->string('room_id');
            $table->string('event_type'); // usuario_conectado, usuario_desconectado, audio_cambio, posicion_actualizada, etc.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->json('event_data'); // Datos específicos del evento
            $table->json('metadata')->nullable(); // Metadatos adicionales
            $table->timestamp('timestamp');
            $table->timestamps();
            
            $table->index(['room_id', 'event_type']);
            $table->index(['room_id', 'timestamp']);
            $table->index(['usuario_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unity_room_events');
    }
};