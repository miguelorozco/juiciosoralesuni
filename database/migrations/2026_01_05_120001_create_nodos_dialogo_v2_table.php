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
        Schema::create('nodos_dialogo_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialogo_id')->constrained('dialogos_v2')->onDelete('cascade');
            $table->foreignId('rol_id')->nullable()->constrained('roles_disponibles')->onDelete('set null');
            $table->string('titulo', 200);
            $table->text('contenido');
            $table->text('instrucciones')->nullable();
            $table->enum('tipo', ['inicio', 'desarrollo', 'decision', 'final'])->default('desarrollo');
            $table->integer('posicion_x')->default(0);
            $table->integer('posicion_y')->default(0);
            $table->boolean('es_inicial')->default(false);
            $table->boolean('es_final')->default(false);
            $table->json('condiciones')->nullable();
            $table->json('consecuencias')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index('dialogo_id', 'idx_dialogo_id');
            $table->index('rol_id', 'idx_rol_id');
            $table->index('tipo', 'idx_tipo');
            $table->index('es_inicial', 'idx_es_inicial');
            $table->index('es_final', 'idx_es_final');
            $table->index(['posicion_x', 'posicion_y'], 'idx_posicion');
            $table->index(['dialogo_id', 'tipo'], 'idx_dialogo_tipo');
            $table->index(['dialogo_id', 'es_inicial'], 'idx_dialogo_inicial');
            $table->index(['dialogo_id', 'es_final'], 'idx_dialogo_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodos_dialogo_v2');
    }
};
