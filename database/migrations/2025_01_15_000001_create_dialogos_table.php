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
        Schema::create('dialogos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->unsignedBigInteger('plantilla_id')->nullable();
            $table->boolean('publico')->default(false);
            $table->enum('estado', ['borrador', 'activo', 'archivado'])->default('borrador');
            $table->json('configuracion')->nullable(); // Configuraciones específicas del diálogo
            $table->softDeletes(); // Soft delete para diálogos
            $table->timestamps();
            
            $table->index('creado_por');
            $table->index('estado');
            $table->index('publico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialogos');
    }
};
