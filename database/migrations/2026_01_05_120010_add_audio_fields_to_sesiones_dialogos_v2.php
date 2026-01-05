<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega campos para almacenar archivo de audio MP3 completo de la sesión de diálogo:
     * - audio_mp3_completo: Ruta al archivo MP3 de toda la sesión
     * - audio_duracion_completo: Duración total del audio en segundos
     * - audio_grabado_en: Fecha y hora en que se inició la grabación
     * - audio_procesado: Si el audio fue procesado/validado
     * - audio_habilitado: Si la grabación está habilitada para esta sesión
     */
    public function up(): void
    {
        Schema::table('sesiones_dialogos_v2', function (Blueprint $table) {
            // Campos de audio MP3 completo de la sesión
            $table->string('audio_mp3_completo', 500)
                  ->nullable()
                  ->after('historial_nodos')
                  ->comment('Ruta al archivo MP3 de la grabación completa de la sesión');
            
            $table->integer('audio_duracion_completo')
                  ->nullable()
                  ->after('audio_mp3_completo')
                  ->comment('Duración total del audio completo en segundos');
            
            $table->timestamp('audio_grabado_en')
                  ->nullable()
                  ->after('audio_duracion_completo')
                  ->comment('Fecha y hora en que se inició la grabación del audio completo');
            
            $table->boolean('audio_procesado')
                  ->default(false)
                  ->after('audio_grabado_en')
                  ->comment('Indica si el audio completo fue procesado y validado');
            
            $table->boolean('audio_habilitado')
                  ->default(false)
                  ->after('audio_procesado')
                  ->comment('Indica si la grabación de audio está habilitada para esta sesión');
            
            // Índices
            $table->index('audio_habilitado', 'idx_audio_habilitado');
            $table->index('audio_procesado', 'idx_audio_procesado');
            $table->index(['sesion_id', 'audio_habilitado'], 'idx_sesion_audio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesiones_dialogos_v2', function (Blueprint $table) {
            $table->dropIndex('idx_audio_habilitado');
            $table->dropIndex('idx_audio_procesado');
            $table->dropIndex('idx_sesion_audio');
            
            $table->dropColumn([
                'audio_mp3_completo',
                'audio_duracion_completo',
                'audio_grabado_en',
                'audio_procesado',
                'audio_habilitado'
            ]);
        });
    }
};
