<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalmonBossesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salmon_bosses', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->statInkKey('key');
            $table->string('splatnet', 32);
        });

        $salmon_bosses = [
            ['goldie',     3, 'sakelien-golden'],
            ['steelhead',  6, 'sakelien-bomber'],
            ['flyfish',    9, 'sakelien-cup-twins'],
            ['scrapper',  12, 'sakelien-shield'],
            ['steel_eel', 13, 'sakelien-snake'],
            ['stinger',   14, 'sakelien-tower'],
            ['maws',      15, 'sakediver'],
            ['griller',   16, 'sakedozer'],
            ['drizzler',  21, 'sakerocket'],
        ];

        foreach ($salmon_bosses as $boss) {
            DB::table('salmon_bosses')->insert([
                'id' => $boss[1],
                'key' => $boss[0],
                'splatnet' => $boss[2]
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salmon_bosses');
    }
}
