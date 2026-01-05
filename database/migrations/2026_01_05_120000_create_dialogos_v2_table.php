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
        Schema::create('dialogos_v2', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->foreignId('creado_por')->constrained('users')->onDelete('restrict');
            $table->foreignId('plantilla_id')->nullable()->constrained('plantillas_sesiones')->onDelete('set null');
            $table->boolean('publico')->default(false);
            $table->enum('estado', ['borrador', 'activo', 'archivado'])->default('borrador');
            $table->string('version', 20)->nullable()->default('1.0.0');
            $table->json('configuracion')->nullable();
            $table->json('metadata_unity')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index('creado_por', 'idx_creado_por');
            $table->index('estado', 'idx_estado');
            $table->index('publico', 'idx_publico');
            $table->index('plantilla_id', 'idx_plantilla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialogos_v2');
    }
};
