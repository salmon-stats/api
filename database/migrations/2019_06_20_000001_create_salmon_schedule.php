<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalmonSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salmon_schedules', function (Blueprint $table) {
            $table->dateTime('schedule_id')->primary();
            $table->dateTime('end_at');
            $table->json('weapons'); // array of weapon_id
            $table->unsignedTinyInteger('stage_id');

            $table->foreign('stage_id')->references('id')->on('salmon_stages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salmon_schedules');
    }
}
