<?php

use Illuminate\Database\Seeder;

class SalmonSchedulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('salmon_schedules')->insert([
            'schedule_id' => '2017-01-01 00:00:00',
            'end_at' => '2017-01-02 00:00:00',
            'weapons' => json_encode([40, 60, 70, 2010]),
            'stage_id' => 1,
        ]);
    }
}
