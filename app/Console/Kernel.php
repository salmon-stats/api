<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

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
        $schedule
            ->call(function() {
                $upcomingScheduleCount = \App\SalmonSchedule::where(
                        'end_at',
                        '>',
                        DB::raw('NOW()'),
                    )
                    ->get()
                    ->count();
                $shouldFetchFutureSchedules = $upcomingScheduleCount < 2;

                if ($shouldFetchFutureSchedules) {
                    $salmonScheduleFetcher = new \App\Helpers\SalmonScheduleFetcher();
                    $salmonScheduleFetcher->fetchFutureSchedules();
                }
            })
            ->everyFiveMinutes();
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
