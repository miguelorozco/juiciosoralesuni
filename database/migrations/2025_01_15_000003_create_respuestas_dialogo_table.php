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
        Schema::create('respuestas_dialogo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nodo_padre_id')->constrained('nodos_dialogo')->onDelete('cascade');
            $table->foreignId('nodo_siguiente_id')->nullable()->constrained('nodos_dialogo')->onDelete('set null');
            $table->string('texto', 500); // Texto de la opción de respuesta
            $table->text('descripcion')->nullable(); // Descripción adicional de la respuesta
            $table->integer('orden')->default(0); // Orden de las opciones
            $table->json('condiciones')->nullable(); // Condiciones para mostrar esta respuesta
            $table->json('consecuencias')->nullable(); // Consecuencias de esta elección
            $table->integer('puntuacion')->default(0); // Puntuación para evaluación
            $table->string('color', 7)->default('#007bff'); // Color para visualización
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('nodo_padre_id');
            $table->index('nodo_siguiente_id');
            $table->index(['nodo_padre_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_dialogo');
    }
};
