<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use function GuzzleHttp\json_encode;

class SalmonResultsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $members = ['2cf69d9715b323b7', 'cef1abd3b519faa7', 'e74f5fd8d37b9416', '8059a7c9570ecacd'];
        sort($members);

        DB::table('salmon_results')->insert([
            'schedule_id' => '2017-01-01 00:00:00',
            'start_at' => '2017-01-01 02:34:56',
            'members' => json_encode($members),
            'uploader_user_id' => 1,
            'clear_waves' => 3,
            'fail_reason_id' => null,
            'danger_rate' => 80,
        ]);
    }
}
