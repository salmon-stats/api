<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalmonResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salmon_fail_reasons', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->statInkKey('key');
        });

        $failReasons = [
            'wipe_out',
            'time_limit',
        ];

        foreach ($failReasons as $key) {
            DB::table('salmon_fail_reasons')->insert(['key' => $key]);
        }

        Schema::create('salmon_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('schedule_id');
            $table->dateTime('start_at');
            $table->json('members'); // array of pids
            $table->json('boss_appearances');
            $table->unsignedBigInteger('uploader_user_id');
            $table->unsignedTinyInteger('clear_waves');
            $table->unsignedTinyInteger('fail_reason_id')->nullable();
            $table->decimal('danger_rate', 4, 1);
            $table->timestamps();

            $table->foreign('schedule_id')->references('schedule_id')->on('salmon_schedules');
            $table->foreign('uploader_user_id')->references('id')->on('users');
            $table->foreign('fail_reason_id')->references('id')->on('salmon_fail_reasons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salmon_results');
        Schema::dropIfExists('salmon_fail_reasons');
    }
}
