<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ValidateDialogosMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dialogos:validate-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valida que la migración de diálogos v1 a v2 se haya realizado correctamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
            $this->error("   Archivo: {$e->getFile()}");
            $this->error("   Línea: {$e->getLine()}");
            return Command::FAILURE;
        }
    }
}
