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
            $table->string('name', 64);
            $table->string('splatnet', 32);
        });
        $salmon_events = [
            ['cohock_charge', 'Cohock Charge', 'cohock-charge'],
            ['fog', 'Fog', 'fog'],
            ['goldie_seeking', 'Goldie Seeking', 'goldie-seeking'],
            ['griller', 'The Griller', 'griller'],
            ['mothership', 'The Mothership', 'the-mothership'],
            ['rush', 'Rush', 'rush'],
        ];
        foreach ($salmon_events as $salmon_event) {
            DB::table('salmon_events')->insert([
                'key' => $salmon_event[0],
                'name' => $salmon_event[1],
                'splatnet' => $salmon_event[2],
            ]);
        }

        Schema::create('salmon_water_levels', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->statInkKey('key');
            $table->string('name', 64);
            $table->string('splatnet', 32);
        });
        $salmon_water_levels = [
            ['low', 'Low Tide', 'low'],
            ['normal', 'Mid Tide', 'normal'],
            ['high', 'High Tide', 'high'],
        ];
        foreach ($salmon_water_levels as $salmon_water_level) {
            DB::table('salmon_water_levels')->insert([
                'key' => $salmon_water_level[0],
                'name' => $salmon_water_level[1],
                'splatnet' => $salmon_water_level[2]
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