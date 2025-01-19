<?php

namespace App\Console\Commands;

use App\Services\QR\DirectTransferService;
use Exception;
use Illuminate\Console\Command;

class CleanDirectTransfers extends Command
{
    protected $signature = 'files:clean-direct-transfers';
    protected $description = 'Clean used and expired direct transfers';

    public function handle(DirectTransferService $directTransferService)
    {
        try {
            $this->info('🚀 Starting cleanup of direct transfers...');

            $directTransferService->cleanDirectTransfers();

            $this->info('✅ Direct transfers cleaned successfully');
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
