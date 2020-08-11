<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salmon-stats:fetch-schedules {--future} {--bypass-checking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Salmon Run schedules from spla2.yuu26.com';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $bypassChecking = $this->option('bypass-checking');
        $this->info("FetchSchedules: Started" . ($bypassChecking ? ' (Bypass checking)' : ''));

        if (!$bypassChecking) {
            $upcomingScheduleCount = \App\SalmonSchedule::where(
                    'end_at',
                    '>',
                    DB::raw('NOW()'),
                )
                ->get()
                ->count();

            $shouldFetchFutureSchedules = $upcomingScheduleCount < 2;
            if (!$shouldFetchFutureSchedules) {
                $this->info("FetchSchedules: Canceled because $upcomingScheduleCount future schedules already exists.");
                return;
            }
        }

        $salmonScheduleFetcher = new \App\Helpers\SalmonScheduleFetcher();

        if ($this->option('future')) {
            $salmonScheduleFetcher->fetchFutureSchedules();
            $this->info('FetchSchedules: Successfully fetched future schedules.');
        } else {
            $salmonScheduleFetcher->fetchPastSchedules();
            $this->info('FetchSchedules: Successfully fetched past schedules.');
        }
    }
}
