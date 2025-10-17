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
        Schema::create('asignaciones_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones_juicios')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('rol_id')->constrained('roles_disponibles');
            $table->foreignId('asignado_por')->constrained('users');
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->boolean('confirmado')->default(false);
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->unique(['sesion_id', 'usuario_id']);
            $table->unique(['sesion_id', 'rol_id']);
            $table->index('sesion_id');
            $table->index('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_roles');
    }
};
