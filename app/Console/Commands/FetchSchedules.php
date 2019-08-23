<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salmon-stats:fetch-schedules';

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
     * @return mixed
     */
    public function handle()
    {
        $salmonScheduleFetcher = new \App\Helpers\SalmonScheduleFetcher();
        $salmonScheduleFetcher->fetchPastSchedules();
    }
}
