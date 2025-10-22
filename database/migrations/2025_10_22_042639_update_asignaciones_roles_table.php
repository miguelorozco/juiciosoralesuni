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
        Schema::table('asignaciones_roles', function (Blueprint $table) {
            // Agregar nueva columna
            $table->unsignedBigInteger('rol_dialogo_id')->nullable()->after('usuario_id');
            
            // Agregar foreign key
            $table->foreign('rol_dialogo_id')->references('id')->on('roles_dialogo')->onDelete('cascade');
            
            // Agregar índice
            $table->index('rol_dialogo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_roles', function (Blueprint $table) {
            $table->dropForeign(['rol_dialogo_id']);
            $table->dropIndex(['rol_dialogo_id']);
            $table->dropColumn('rol_dialogo_id');
        });
    }
};