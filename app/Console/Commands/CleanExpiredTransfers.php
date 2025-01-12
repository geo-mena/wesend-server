<?php

namespace App\Console\Commands;

use App\Services\TransferService;
use Exception;
use Illuminate\Console\Command;

class CleanExpiredTransfers extends Command
{
    protected $signature = 'files:clean-transfers';
    protected $description = 'Clean expired transfers';

    public function handle(TransferService $transferService)
    {
        try {
            $this->info('🚀 Starting cleanup of expired transfers...');

            $transferService->deleteExpiredTransfers();

            $this->info('✅ Expired transfers cleaned successfully');
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
