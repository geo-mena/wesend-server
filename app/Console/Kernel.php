<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('files:clean-transfers')->everyMinute(); //! Cada 24 horas
        $schedule->command('files:clean-orphaned')->everyMinute(); //! Cada 24 horas
        $schedule->command('uploads:clean-orphaned')->everyMinute(); //! Cada 24 horas
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
