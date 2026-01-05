<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateDialogosToV2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dialogos:migrate-to-v2 
                            {--validate-only : Solo validar sin migrar}
                            {--force : Forzar migración incluso si ya existen datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los datos del sistema de diálogos v1 a v2';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('MIGRACIÓN DE DATOS - DIÁLOGOS v1 → v2');
        $this->info('========================================');
        $this->newLine();

        // Verificar que las tablas v2 existan
        $tablasV2 = ['dialogos_v2', 'nodos_dialogo_v2', 'respuestas_dialogo_v2', 'sesiones_dialogos_v2', 'decisiones_dialogo_v2'];
        foreach ($tablasV2 as $tabla) {
            if (!Schema::hasTable($tabla)) {
                $this->error("❌ ERROR: La tabla {$tabla} no existe. Ejecuta las migraciones primero.");
                $this->info("   Ejecuta: php artisan migrate");
                return Command::FAILURE;
            }
        }

        $this->info('✓ Todas las tablas v2 existen');
        $this->newLine();

        // Verificar si ya hay datos
        $tieneDatos = DB::table('dialogos_v2')->count() > 0;
        if ($tieneDatos && !$this->option('force')) {
            if (!$this->confirm('⚠ Las tablas v2 ya contienen datos. ¿Deseas continuar? Esto puede duplicar registros.')) {
                $this->warn('Migración cancelada.');
                return Command::FAILURE;
            }
        }

        // Si solo validar
        if ($this->option('validate-only')) {
            return $this->validateMigration();
        }

        // Ejecutar migración
        $this->info('Iniciando migración de datos...');
        $this->newLine();

        // Cargar y ejecutar el script de migración
        $scriptPath = database_path('scripts/migrar-datos-dialogos-v2.php');
        
        if (!file_exists($scriptPath)) {
            $this->error("❌ No se encontró el script de migración: {$scriptPath}");
            return Command::FAILURE;
        }

        try {
            // Ejecutar el script dentro del contexto de Laravel
            ob_start();
            $result = require $scriptPath;
            $output = ob_get_clean();
            
            $this->line($output);
            
            if (is_array($result) && isset($result['errores']) && $result['errores'] > 0) {
                $this->warn("⚠ Migración completada con {$result['errores']} errores.");
                return Command::SUCCESS;
            }
            
            $this->info('✓ Migración completada exitosamente');
            $this->newLine();
            $this->info('Ejecuta: php artisan dialogos:validate-migration para validar los datos');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la migración: {$e->getMessage()}");
            $this->error("   Archivo: {$e->getFile()}");
            $this->error("   Línea: {$e->getLine()}");
            return Command::FAILURE;
        }
    }

    /**
     * Validar la migración sin ejecutarla
     */
    protected function validateMigration()
    {
        $this->info('Validando migración...');
        $this->newLine();

        $scriptPath = database_path('scripts/validar-migracion-dialogos.php');
        
        if (!file_exists($scriptPath)) {
            $this->error("❌ No se encontró el script de validación: {$scriptPath}");
            return Command::FAILURE;
        }

        try {
            ob_start();
            $result = require $scriptPath;
            $output = ob_get_clean();
            
            $this->line($output);
            
            if (is_array($result) && isset($result['valido']) && $result['valido']) {
                return Command::SUCCESS;
            }
            
            return Command::FAILURE;
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la validación: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
