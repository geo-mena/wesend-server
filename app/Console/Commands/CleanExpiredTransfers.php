<?php

namespace App\Console\Commands;

use App\Services\TransferService;
use Illuminate\Console\Command;

class CleanExpiredTransfers extends Command
{
    protected $signature = 'files:clean-transfers';
    protected $description = 'Clean expired transfers';

    public function handle(TransferService $transferService)
    {
        $this->info('🚀 Starting cleanup of expired transfers...');

        $transferService->deleteExpiredTransfers();

        $this->info('✅ Expired transfers cleaned successfully');
    }
}
