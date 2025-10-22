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
        // Add foreign key for dialogos.plantilla_id -> plantillas_sesiones.id
        Schema::table('dialogos', function (Blueprint $table) {
            $table->foreign('plantilla_id')->references('id')->on('plantillas_sesiones')->onDelete('set null');
        });

        // Add foreign keys for respuestas_dialogo
        Schema::table('respuestas_dialogo', function (Blueprint $table) {
            $table->foreign('nodo_padre_id')->references('id')->on('nodos_dialogo')->onDelete('cascade');
            $table->foreign('nodo_siguiente_id')->references('id')->on('nodos_dialogo')->onDelete('set null');
        });

        // Add foreign keys for sesiones_dialogos
        Schema::table('sesiones_dialogos', function (Blueprint $table) {
            $table->foreign('sesion_id')->references('id')->on('sesiones_juicios')->onDelete('cascade');
            $table->foreign('dialogo_id')->references('id')->on('dialogos')->onDelete('cascade');
            $table->foreign('nodo_actual_id')->references('id')->on('nodos_dialogo')->onDelete('set null');
        });

        // Add foreign keys for decisiones_sesion
        Schema::table('decisiones_sesion', function (Blueprint $table) {
            $table->foreign('sesion_id')->references('id')->on('sesiones_juicios')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users');
            $table->foreign('rol_id')->references('id')->on('roles_disponibles');
            $table->foreign('nodo_dialogo_id')->references('id')->on('nodos_dialogo');
            $table->foreign('respuesta_id')->references('id')->on('respuestas_dialogo')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys in reverse order
        Schema::table('decisiones_sesion', function (Blueprint $table) {
            $table->dropForeign(['respuesta_id']);
            $table->dropForeign(['nodo_dialogo_id']);
            $table->dropForeign(['rol_id']);
            $table->dropForeign(['usuario_id']);
            $table->dropForeign(['sesion_id']);
        });

        Schema::table('sesiones_dialogos', function (Blueprint $table) {
            $table->dropForeign(['nodo_actual_id']);
            $table->dropForeign(['dialogo_id']);
            $table->dropForeign(['sesion_id']);
        });

        Schema::table('respuestas_dialogo', function (Blueprint $table) {
            $table->dropForeign(['nodo_siguiente_id']);
            $table->dropForeign(['nodo_padre_id']);
        });

        Schema::table('dialogos', function (Blueprint $table) {
            $table->dropForeign(['plantilla_id']);
        });
    }
};