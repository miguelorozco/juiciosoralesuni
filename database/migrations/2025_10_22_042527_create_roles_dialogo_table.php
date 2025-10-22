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
        Schema::create('roles_dialogo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialogo_id')->constrained('dialogos')->onDelete('cascade');
            $table->string('nombre', 100); // Nombre del rol específico para este diálogo
            $table->text('descripcion')->nullable(); // Descripción del rol en este contexto
            $table->string('icono', 50)->default('person'); // Icono para mostrar en UI
            $table->integer('orden')->default(0); // Orden de aparición
            $table->boolean('requerido')->default(true); // Si el rol es obligatorio
            $table->boolean('activo')->default(true);
            $table->json('configuracion')->nullable(); // Configuración específica del rol
            $table->timestamps();
            
            $table->index('dialogo_id');
            $table->index(['dialogo_id', 'activo']);
            $table->index(['dialogo_id', 'orden']);
            $table->unique(['dialogo_id', 'nombre']); // Un rol único por diálogo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_dialogo');
    }
};