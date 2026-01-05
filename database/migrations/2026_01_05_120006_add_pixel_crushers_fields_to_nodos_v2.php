<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega campos adicionales para alinear con Pixel Crushers Dialogue System:
     * - conversant_id: Quien escucha el diálogo (equivalente a ConversantID)
     * - menu_text: Texto para mostrar en menú de respuestas (equivalente a MenuText)
     * - Actualiza tipo enum para incluir 'agrupacion' (equivalente a isGroup)
     */
    public function up(): void
    {
        Schema::table('nodos_dialogo_v2', function (Blueprint $table) {
            // Conversant: Quien escucha el diálogo (equivalente a ConversantID en Pixel Crushers)
            $table->foreignId('conversant_id')
                  ->nullable()
                  ->after('rol_id')
                  ->constrained('roles_disponibles')
                  ->onDelete('set null');
            
            // Menu Text: Texto para mostrar en menú de respuestas (equivalente a MenuText en Pixel Crushers)
            $table->text('menu_text')
                  ->nullable()
                  ->after('contenido')
                  ->comment('Texto para mostrar en menú de respuestas (equivalente a MenuText de Pixel Crushers)');
            
            // Índice para conversant_id
            $table->index('conversant_id', 'idx_conversant_id');
        });
        
        // Actualizar enum tipo para incluir 'agrupacion' (equivalente a isGroup en Pixel Crushers)
        // Nota: MySQL no permite modificar ENUM directamente, necesitamos hacerlo manualmente
        // Por ahora, usaremos metadata para marcar nodos de agrupación
        // O podemos crear una migración separada para cambiar el enum
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodos_dialogo_v2', function (Blueprint $table) {
            $table->dropForeign(['conversant_id']);
            $table->dropIndex('idx_conversant_id');
            $table->dropColumn(['conversant_id', 'menu_text']);
        });
    }
};
