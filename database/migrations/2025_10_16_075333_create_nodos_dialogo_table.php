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
        Schema::create('nodos_dialogo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialogo_id')->constrained('dialogos')->onDelete('cascade');
            $table->foreignId('rol_id')->nullable()->constrained('roles_disponibles')->onDelete('set null');
            $table->string('titulo', 200);
            $table->text('contenido');
            $table->text('instrucciones')->nullable();
            $table->integer('orden')->default(0);
            $table->enum('tipo', ['inicio', 'desarrollo', 'decision', 'final'])->default('desarrollo');
            $table->json('condiciones')->nullable(); // Condiciones para mostrar el nodo
            $table->json('metadata')->nullable(); // Metadatos adicionales (posiciones, estilos, etc.)
            $table->boolean('es_inicial')->default(false);
            $table->boolean('es_final')->default(false);
            $table->timestamps();
            
            $table->index('dialogo_id');
            $table->index('rol_id');
            $table->index(['dialogo_id', 'orden']);
            $table->index(['dialogo_id', 'es_inicial']);
            $table->index(['dialogo_id', 'es_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodos_dialogo');
    }
};