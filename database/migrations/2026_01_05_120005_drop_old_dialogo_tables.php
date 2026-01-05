<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * IMPORTANTE: Esta migración elimina las tablas antiguas del sistema de diálogos.
     * Solo ejecutar DESPUÉS de migrar todos los datos a las tablas v2.
     */
    public function up(): void
    {
        // Eliminar foreign keys primero para evitar problemas de restricciones
        
        // 1. Eliminar foreign key de asignaciones_roles a roles_dialogo
        if (Schema::hasTable('asignaciones_roles')) {
            Schema::table('asignaciones_roles', function (Blueprint $table) {
                if (Schema::hasColumn('asignaciones_roles', 'rol_dialogo_id')) {
                    $table->dropForeign(['rol_dialogo_id']);
                }
            });
        }
        
        // Eliminar en orden inverso de dependencias para evitar problemas de foreign keys
        
        // 2. Eliminar decisiones (depende de otras tablas)
        Schema::dropIfExists('decisiones_sesion');
        
        // 3. Eliminar sesiones de diálogos
        Schema::dropIfExists('sesiones_dialogos');
        
        // 4. Eliminar respuestas
        Schema::dropIfExists('respuestas_dialogo');
        
        // 5. Eliminar nodos
        Schema::dropIfExists('nodos_dialogo');
        
        // 6. Eliminar roles_dialogo (tiene foreign key a dialogos)
        Schema::dropIfExists('roles_dialogo');
        
        // 7. Eliminar diálogos (última, es la tabla principal)
        Schema::dropIfExists('dialogos');
    }

    /**
     * Reverse the migrations.
     * 
     * NOTA: No se puede revertir esta migración ya que las tablas fueron eliminadas.
     * Si es necesario restaurar, usar los backups creados previamente.
     */
    public function down(): void
    {
        // No se puede revertir - las tablas fueron eliminadas
        // Para restaurar, usar los backups en storage/app/backups/dialogos/
        throw new \Exception('No se puede revertir la eliminación de tablas. Use los backups para restaurar.');
    }
};
