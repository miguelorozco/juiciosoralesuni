<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use Illuminate\Console\Command;

class CleanupLoginAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:cleanup-login-attempts {--days=30 : Number of days to keep login attempts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old login attempts to maintain database performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        
        $deleted = LoginAttempt::cleanupOldAttempts($days);
        
        $this->info("Cleaned up {$deleted} old login attempts older than {$days} days.");
        
        return Command::SUCCESS;
    }
}