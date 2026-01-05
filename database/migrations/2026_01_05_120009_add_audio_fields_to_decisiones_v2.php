<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega campos para almacenar archivos de audio MP3 de las decisiones:
     * - audio_mp3: Ruta al archivo MP3 de la decisión
     * - audio_duracion: Duración del audio en segundos
     * - audio_grabado_en: Fecha y hora en que se grabó el audio
     * - audio_procesado: Si el audio fue procesado/validado
     */
    public function up(): void
    {
        Schema::table('decisiones_dialogo_v2', function (Blueprint $table) {
            // Campos de audio MP3
            $table->string('audio_mp3', 500)
                  ->nullable()
                  ->after('retroalimentacion')
                  ->comment('Ruta al archivo MP3 de la grabación de la decisión');
            
            $table->integer('audio_duracion')
                  ->nullable()
                  ->after('audio_mp3')
                  ->comment('Duración del audio en segundos');
            
            $table->timestamp('audio_grabado_en')
                  ->nullable()
                  ->after('audio_duracion')
                  ->comment('Fecha y hora en que se grabó el audio');
            
            $table->boolean('audio_procesado')
                  ->default(false)
                  ->after('audio_grabado_en')
                  ->comment('Indica si el audio fue procesado y validado');
            
            // Índices para búsquedas de audio
            $table->index('audio_procesado', 'idx_audio_procesado');
            $table->index('audio_grabado_en', 'idx_audio_grabado_en');
            $table->index(['usuario_id', 'audio_procesado'], 'idx_usuario_audio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decisiones_dialogo_v2', function (Blueprint $table) {
            $table->dropIndex('idx_audio_procesado');
            $table->dropIndex('idx_audio_grabado_en');
            $table->dropIndex('idx_usuario_audio');
            
            $table->dropColumn([
                'audio_mp3',
                'audio_duracion',
                'audio_grabado_en',
                'audio_procesado'
            ]);
        });
    }
};
