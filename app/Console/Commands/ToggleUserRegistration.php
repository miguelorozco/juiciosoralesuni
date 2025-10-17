<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToggleUserRegistration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registration:toggle {status : enable|disable|status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle user registration status (enable/disable/status)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $status = $this->argument('status');
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->error('Archivo .env no encontrado');
            return 1;
        }
        
        $envContent = File::get($envPath);
        
        switch ($status) {
            case 'enable':
                $this->updateEnvFile($envContent, $envPath, 'TRUE');
                $this->info('✅ Registro de usuarios HABILITADO');
                break;
                
            case 'disable':
                $this->updateEnvFile($envContent, $envPath, 'FALSE');
                $this->info('❌ Registro de usuarios DESHABILITADO');
                break;
                
            case 'status':
                $this->showCurrentStatus();
                break;
                
            default:
                $this->error('Estado inválido. Use: enable, disable, o status');
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Update the .env file with new registration status
     */
    private function updateEnvFile($envContent, $envPath, $newValue)
    {
        if (strpos($envContent, 'ALLOW_NEW_USER=') !== false) {
            // Update existing line
            $updatedContent = preg_replace(
                '/ALLOW_NEW_USER=.*/',
                "ALLOW_NEW_USER={$newValue}",
                $envContent
            );
        } else {
            // Add new line
            $updatedContent = $envContent . "\n# Enable New User Registration\nALLOW_NEW_USER={$newValue}\n";
        }
        
        File::put($envPath, $updatedContent);
        
        // Clear config cache
        $this->call('config:clear');
    }
    
    /**
     * Show current registration status
     */
    private function showCurrentStatus()
    {
        $isEnabled = config('app.allow_new_user', true);
        
        $this->info('📊 Estado actual del registro de usuarios:');
        $this->line('');
        
        if ($isEnabled) {
            $this->line('✅ <fg=green>HABILITADO</fg=green> - Los usuarios pueden registrarse');
        } else {
            $this->line('❌ <fg=red>DESHABILITADO</fg=red> - Los usuarios NO pueden registrarse');
        }
        
        $this->line('');
        $this->line('Para cambiar el estado, use:');
        $this->line('  php artisan registration:toggle enable   - Habilitar registro');
        $this->line('  php artisan registration:toggle disable  - Deshabilitar registro');
    }
}