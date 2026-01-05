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
        Schema::create('respuestas_dialogo_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nodo_padre_id')->constrained('nodos_dialogo_v2')->onDelete('cascade');
            $table->foreignId('nodo_siguiente_id')->nullable()->constrained('nodos_dialogo_v2')->onDelete('set null');
            $table->string('texto', 500);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->integer('puntuacion')->default(0);
            $table->string('color', 7)->default('#007bff');
            $table->json('condiciones')->nullable();
            $table->json('consecuencias')->nullable();
            $table->boolean('requiere_usuario_registrado')->default(false);
            $table->boolean('es_opcion_por_defecto')->default(false);
            $table->json('requiere_rol')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index('nodo_padre_id', 'idx_nodo_padre');
            $table->index('nodo_siguiente_id', 'idx_nodo_siguiente');
            $table->index('activo', 'idx_activo');
            $table->index('requiere_usuario_registrado', 'idx_requiere_registrado');
            $table->index('es_opcion_por_defecto', 'idx_opcion_defecto');
            $table->index(['nodo_padre_id', 'activo'], 'idx_nodo_padre_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_dialogo_v2');
    }
};
