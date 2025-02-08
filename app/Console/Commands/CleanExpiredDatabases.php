<?php

namespace App\Console\Commands;

use App\Services\Database\DatabaseService;
use Illuminate\Console\Command;
use Exception;

class CleanExpiredDatabases extends Command
{
    protected $signature = 'databases:clean';
    protected $description = 'Clean expired database records';

    public function handle(DatabaseService $service)
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
