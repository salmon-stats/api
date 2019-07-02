<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalmonWavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salmon_events', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->statInkKey('key');
            $table->string('splatnet', 32);
        });
        $salmonEvents = [
            ['cohock_charge', 'cohock-charge'],
            ['fog', 'fog'],
            ['goldie_seeking', 'goldie-seeking'],
            ['griller', 'griller'],
            ['mothership', 'the-mothership'],
            ['rush', 'rush'],
        ];
        foreach ($salmonEvents as $salmonEvent) {
            DB::table('salmon_events')->insert([
                'key' => $salmonEvent[0],
                'splatnet' => $salmonEvent[1],
            ]);
        }

        Schema::create('salmon_water_levels', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->statInkKey('key');
            $table->string('splatnet', 32);
        });
        $waterLevels = [
            ['low', 'low'],
            ['normal', 'normal'],
            ['high', 'high'],
        ];
        foreach ($waterLevels as $waterLevel) {
            DB::table('salmon_water_levels')->insert([
                'key' => $waterLevel[0],
                'splatnet' => $waterLevel[1],
            ]);
        }

        Schema::create('salmon_waves', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->unsignedTinyInteger('wave');
            $table->unsignedTinyInteger('event_id')->nullable();
            $table->unsignedTinyInteger('water_id');
            $table->unsignedSmallInteger('golden_egg_quota')->integer()->null();
            $table->unsignedSmallInteger('golden_egg_appearances')->integer()->null();
            $table->unsignedSmallInteger('golden_egg_delivered')->integer()->null();
            $table->unsignedSmallInteger('power_egg_collected')->integer()->null();

            $table->primary(['salmon_id', 'wave']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results');
            $table->foreign('event_id')->references('id')->on('salmon_events');
            $table->foreign('water_id')->references('id')->on('salmon_water_levels');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salmon_waves');
        Schema::dropIfExists('salmon_events');
        Schema::dropIfExists('salmon_water_levels');
    }
}
