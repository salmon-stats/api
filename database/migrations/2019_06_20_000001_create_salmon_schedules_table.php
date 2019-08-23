<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Carbon\Carbon;
use GuzzleHttp\Client;
use function GuzzleHttp\json_decode;

class CreateSalmonSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::create('salmon_schedules', function (Blueprint $table) {
                $table->dateTime('schedule_id')->primary();
                $table->dateTime('end_at');
                $table->json('weapons'); // array of weapon_id
                $table->unsignedTinyInteger('stage_id');
                $table->unsignedSmallInteger('rare_weapon_id')->nullable();

                $table->foreign('stage_id')->references('id')->on('salmon_stages')->onDelete('cascade');
                // $table->foreign('rare_weapon_id')->references('id')->on('salmon_weapons')->onDelete('cascade');
            });
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
