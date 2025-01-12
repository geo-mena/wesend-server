<?php

namespace App\Console\Commands;

use App\Services\RateLimitService;
use Exception;
use Illuminate\Console\Command;

class CleanOrphanedUploads extends Command
{
    protected $signature = 'uploads:clean-orphaned';
    protected $description = 'Clean orphaned uploads from Redis';

    protected $rateLimitService;

    public function __construct(RateLimitService $rateLimitService)
    {
        parent::__construct();
        $this->rateLimitService = $rateLimitService;
    }

    public function handle()
    {
        try {
            $this->info('🚀 Starting cleanup of orphaned uploads...');

            $this->rateLimitService->cleanOrphanedRecords();

            $this->info('✅ Orphaned uploads cleaned successfully');
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
