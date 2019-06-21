<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'yukinkling2',
            'twitter_id' => 1090304898594308097,
            'player_id' => '2cf69d9715b323b7',
            'api_token' => str_repeat('a', 64),
        ]);
    }
}
