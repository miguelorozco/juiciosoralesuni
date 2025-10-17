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
        Schema::create('plantillas_sesiones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->boolean('publica')->default(false);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->json('configuracion')->nullable();
            $table->timestamps();
            
            $table->index('creado_por');
            $table->index('publica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_sesiones');
    }
};
