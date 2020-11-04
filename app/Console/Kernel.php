<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Services\Notifications\FCMNotifier;
use App\Services\Messaging\MessagingService;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            FCMNotifier::notifyUsers();
            MessagingService::pairUsers();
            MessagingService::notifyForNewMessages();
        })->everyFiveMinutes();
        
        $schedule->call(function () {
            MessagingService::deleteLastWeeksTaskReports();
        })->tuesdays();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
