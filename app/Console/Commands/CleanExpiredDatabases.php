<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanExpiredDatabases extends Command
{
    protected $signature = 'databases:clean';
    protected $description = 'Clean expired database records';

    public function handle(TempDatabaseService $service)
    {
        try {
            $this->info('🚀 Starting cleanup of expired database records...');

            $service->cleanExpiredDatabases();

            $this->info('✅ Expired database records cleaned successfully');
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
