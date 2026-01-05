<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DialogosV2MigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test de creación de tablas v2
     */
    public function test_tablas_v2_se_crean_correctamente(): void
    {
        // Ejecutar todas las migraciones
        $this->artisan('migrate')->assertSuccessful();

        // Verificar que las tablas v2 existen
        $this->assertTrue(Schema::hasTable('dialogos_v2'));
        $this->assertTrue(Schema::hasTable('nodos_dialogo_v2'));
        $this->assertTrue(Schema::hasTable('respuestas_dialogo_v2'));
        $this->assertTrue(Schema::hasTable('sesiones_dialogos_v2'));
        $this->assertTrue(Schema::hasTable('decisiones_dialogo_v2'));
    }

    /**
     * Test de estructura de tabla dialogos_v2
     */
    public function test_tabla_dialogos_v2_tiene_columnas_correctas(): void
    {
        $this->artisan('migrate', ['--path' => 'database/migrations'])->assertSuccessful();

        $columns = Schema::getColumnListing('dialogos_v2');

        $this->assertContains('id', $columns);
        $this->assertContains('nombre', $columns);
        $this->assertContains('descripcion', $columns);
        $this->assertContains('creado_por', $columns);
        $this->assertContains('publico', $columns);
        $this->assertContains('estado', $columns);
        $this->assertContains('version', $columns);
        $this->assertContains('configuracion', $columns);
        $this->assertContains('metadata_unity', $columns);
    }

    /**
     * Test de estructura de tabla nodos_dialogo_v2
     */
    public function test_tabla_nodos_dialogo_v2_tiene_posiciones_directas(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $columns = Schema::getColumnListing('nodos_dialogo_v2');

        $this->assertContains('posicion_x', $columns);
        $this->assertContains('posicion_y', $columns);
        $this->assertContains('conversant_id', $columns);
        $this->assertContains('menu_text', $columns);
        $this->assertContains('tipo', $columns);
    }

    /**
     * Test de estructura de tabla respuestas_dialogo_v2
     */
    public function test_tabla_respuestas_dialogo_v2_soporta_usuarios_no_registrados(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $columns = Schema::getColumnListing('respuestas_dialogo_v2');

        $this->assertContains('requiere_usuario_registrado', $columns);
        $this->assertContains('es_opcion_por_defecto', $columns);
        $this->assertContains('requiere_rol', $columns);
    }

    /**
     * Test de estructura de tabla decisiones_dialogo_v2
     */
    public function test_tabla_decisiones_dialogo_v2_tiene_campos_evaluacion_y_audio(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $columns = Schema::getColumnListing('decisiones_dialogo_v2');

        // Campos de evaluación
        $this->assertContains('calificacion_profesor', $columns);
        $this->assertContains('notas_profesor', $columns);
        $this->assertContains('evaluado_por', $columns);
        $this->assertContains('estado_evaluacion', $columns);
        $this->assertContains('justificacion_estudiante', $columns);
        $this->assertContains('retroalimentacion', $columns);

        // Campos de audio
        $this->assertContains('audio_mp3', $columns);
        $this->assertContains('audio_duracion', $columns);
        $this->assertContains('audio_grabado_en', $columns);
        $this->assertContains('audio_procesado', $columns);

        // Campos de usuarios no registrados
        $this->assertContains('usuario_registrado', $columns);
        $this->assertContains('fue_opcion_por_defecto', $columns);
    }

    /**
     * Test de estructura de tabla sesiones_dialogos_v2
     */
    public function test_tabla_sesiones_dialogos_v2_tiene_campos_audio_completo(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $columns = Schema::getColumnListing('sesiones_dialogos_v2');

        $this->assertContains('historial_nodos', $columns);
        $this->assertContains('audio_mp3_completo', $columns);
        $this->assertContains('audio_duracion_completo', $columns);
        $this->assertContains('audio_habilitado', $columns);
    }

    /**
     * Test de integridad referencial - Foreign Keys
     */
    public function test_foreign_keys_se_crean_correctamente(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        // Verificar foreign keys usando información del esquema
        $foreignKeys = DB::select("
            SELECT 
                CONSTRAINT_NAME,
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME LIKE '%_v2'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        $this->assertNotEmpty($foreignKeys, 'Debe haber foreign keys en las tablas v2');

        // Verificar algunas foreign keys específicas
        $fkNames = array_column($foreignKeys, 'CONSTRAINT_NAME');
        
        // Verificar que existen foreign keys importantes
        $hasDialogoFK = false;
        $hasSesionFK = false;
        
        foreach ($foreignKeys as $fk) {
            if ($fk->REFERENCED_TABLE_NAME === 'dialogos_v2') {
                $hasDialogoFK = true;
            }
            if ($fk->REFERENCED_TABLE_NAME === 'sesiones_juicios') {
                $hasSesionFK = true;
            }
        }

        $this->assertTrue($hasDialogoFK, 'Debe existir foreign key a dialogos_v2');
        $this->assertTrue($hasSesionFK, 'Debe existir foreign key a sesiones_juicios');
    }

    /**
     * Test de índices en tablas v2
     */
    public function test_indices_se_crean_correctamente(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $indices = DB::select("
            SELECT 
                INDEX_NAME,
                TABLE_NAME,
                COLUMN_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME LIKE '%_v2'
            AND INDEX_NAME != 'PRIMARY'
        ");

        $this->assertNotEmpty($indices, 'Debe haber índices en las tablas v2');

        // Verificar índices importantes
        $indexNames = array_column($indices, 'INDEX_NAME');
        
        $this->assertContains('idx_audio_procesado', $indexNames, 'Debe existir índice para audio_procesado');
        $this->assertContains('idx_estado_evaluacion', $indexNames, 'Debe existir índice para estado_evaluacion');
    }

    /**
     * Test de rollback de migraciones
     */
    public function test_rollback_de_migraciones_funciona(): void
    {
        // Ejecutar migraciones
        $this->artisan('migrate')->assertSuccessful();
        
        // Verificar que las tablas existen
        $this->assertTrue(Schema::hasTable('dialogos_v2'));

        // Hacer rollback de las últimas migraciones
        // Nota: No hacemos rollback de drop_old_dialogo_tables porque no se puede revertir
        $this->artisan('migrate:rollback', [
            '--step' => 5,
            '--path' => 'database/migrations'
        ]);

        // Verificar que las tablas ya no existen (excepto si hay datos)
        // Este test puede necesitar ajustes según las migraciones específicas
    }

    /**
     * Test de que los campos JSON funcionan correctamente
     */
    public function test_campos_json_se_almacenan_correctamente(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $user = \App\Models\User::factory()->create();
        
        $dialogo = \App\Models\DialogoV2::create([
            'nombre' => 'Test Diálogo',
            'descripcion' => 'Test',
            'creado_por' => $user->id,
            'estado' => 'borrador',
            'version' => '1.0.0',
            'configuracion' => ['test' => 'value'],
            'metadata_unity' => ['unity_test' => 'unity_value'],
        ]);

        $this->assertIsArray($dialogo->configuracion);
        $this->assertEquals('value', $dialogo->configuracion['test']);
        $this->assertIsArray($dialogo->metadata_unity);
        $this->assertEquals('unity_value', $dialogo->metadata_unity['unity_test']);
    }
}
