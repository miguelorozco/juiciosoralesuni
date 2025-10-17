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
        Schema::create('sesiones_juicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->foreignId('instructor_id')->constrained('users');
            $table->foreignId('plantilla_id')->nullable()->constrained('plantillas_sesiones');
            $table->enum('estado', ['programada', 'en_curso', 'finalizada', 'cancelada'])->default('programada');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->integer('max_participantes')->default(20);
            $table->json('configuracion')->nullable();
            $table->string('unity_room_id', 100)->nullable();
            $table->timestamps();
            
            $table->index('instructor_id');
            $table->index('estado');
            $table->index('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_juicios');
    }
};
