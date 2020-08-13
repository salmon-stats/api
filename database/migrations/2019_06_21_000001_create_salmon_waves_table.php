<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Constants\SalmonStatsConst;

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
            $table->unsignedTinyInteger('id')->primary();
            $table->statInkKey('key');
            $table->string('splatnet', 32);
        });
        foreach (SalmonStatsConst::SALMON_EVENTS as $salmonEvent) {
            DB::table('salmon_events')->insert([
                'id' => $salmonEvent[0],
                'key' => $salmonEvent[1],
                'splatnet' => $salmonEvent[2],
            ]);
        }

        Schema::create('salmon_water_levels', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->statInkKey('key');
            $table->string('splatnet', 32);
        });
        foreach (SalmonStatsConst::SALMON_WATER_LEVELS as $waterLevel) {
            DB::table('salmon_water_levels')->insert([
                'key' => $waterLevel[1],
                'splatnet' => $waterLevel[2],
            ]);
        }

        Schema::create('salmon_waves', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->unsignedTinyInteger('wave');
            $table->unsignedTinyInteger('event_id');
            $table->unsignedTinyInteger('water_id');
            $table->unsignedSmallInteger('golden_egg_quota')->null();
            $table->unsignedSmallInteger('golden_egg_appearances')->null();
            $table->unsignedSmallInteger('golden_egg_delivered')->null();
            $table->unsignedSmallInteger('power_egg_collected')->null();

            $table->primary(['salmon_id', 'wave']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('salmon_events')->onDelete('cascade');
            $table->foreign('water_id')->references('id')->on('salmon_water_levels')->onDelete('cascade');
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
