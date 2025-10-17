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
        Schema::create('plantilla_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantilla_id')->constrained('plantillas_sesiones')->onDelete('cascade');
            $table->foreignId('rol_id')->constrained('roles_disponibles');
            $table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            $table->unique(['plantilla_id', 'rol_id']);
            $table->index('plantilla_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla_asignaciones');
    }
};
