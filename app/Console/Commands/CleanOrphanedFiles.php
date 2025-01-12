<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UploadService;
use Exception;

class CleanOrphanedFiles extends Command
{
    protected $signature = 'files:clean-orphaned';
    protected $description = 'Clean orphaned files from storage';

    public function handle(UploadService $uploadService)
    {
        try {
            $this->info('🚀 Starting cleanup of orphaned files...');

            $uploadService->cleanOrphanedFiles();

            $this->info('✅ Orphaned files cleaned successfully');
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
