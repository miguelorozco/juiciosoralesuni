<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega campos para evaluación del profesor/instructor:
     * - calificacion_profesor: Calificación manual del profesor (0-100)
     * - notas_profesor: Comentarios y notas del profesor
     * - evaluado_por: ID del profesor que evaluó la decisión
     * - fecha_evaluacion: Fecha en que se evaluó
     * - estado_evaluacion: Estado de la evaluación (pendiente, evaluado, revisado)
     * - justificacion_estudiante: Justificación del estudiante sobre su decisión
     * - retroalimentacion: Retroalimentación general para el estudiante
     */
    public function up(): void
    {
        Schema::table('decisiones_dialogo_v2', function (Blueprint $table) {
            // Campos de evaluación del profesor
            $table->integer('calificacion_profesor')
                  ->nullable()
                  ->after('puntuacion_obtenida')
                  ->comment('Calificación manual del profesor (0-100)');
            
            $table->text('notas_profesor')
                  ->nullable()
                  ->after('calificacion_profesor')
                  ->comment('Comentarios y notas del profesor sobre la decisión');
            
            $table->foreignId('evaluado_por')
                  ->nullable()
                  ->after('notas_profesor')
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('ID del profesor/instructor que evaluó la decisión');
            
            $table->timestamp('fecha_evaluacion')
                  ->nullable()
                  ->after('evaluado_por')
                  ->comment('Fecha y hora en que se evaluó la decisión');
            
            $table->enum('estado_evaluacion', ['pendiente', 'evaluado', 'revisado'])
                  ->default('pendiente')
                  ->after('fecha_evaluacion')
                  ->comment('Estado de la evaluación: pendiente, evaluado, revisado');
            
            // Campos adicionales
            $table->text('justificacion_estudiante')
                  ->nullable()
                  ->after('estado_evaluacion')
                  ->comment('Justificación del estudiante sobre su decisión');
            
            $table->text('retroalimentacion')
                  ->nullable()
                  ->after('justificacion_estudiante')
                  ->comment('Retroalimentación general para el estudiante');
            
            // Índices para búsquedas de evaluación
            $table->index('estado_evaluacion', 'idx_estado_evaluacion');
            $table->index('evaluado_por', 'idx_evaluado_por');
            $table->index('fecha_evaluacion', 'idx_fecha_evaluacion');
            $table->index(['usuario_id', 'estado_evaluacion'], 'idx_usuario_evaluacion');
            $table->index(['sesion_dialogo_id', 'estado_evaluacion'], 'idx_sesion_evaluacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decisiones_dialogo_v2', function (Blueprint $table) {
            $table->dropForeign(['evaluado_por']);
            $table->dropIndex('idx_estado_evaluacion');
            $table->dropIndex('idx_evaluado_por');
            $table->dropIndex('idx_fecha_evaluacion');
            $table->dropIndex('idx_usuario_evaluacion');
            $table->dropIndex('idx_sesion_evaluacion');
            
            $table->dropColumn([
                'calificacion_profesor',
                'notas_profesor',
                'evaluado_por',
                'fecha_evaluacion',
                'estado_evaluacion',
                'justificacion_estudiante',
                'retroalimentacion'
            ]);
        });
    }
};
