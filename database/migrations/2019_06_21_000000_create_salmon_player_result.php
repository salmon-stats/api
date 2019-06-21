<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalmonPlayerResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salmon_player_results', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->unsignedBigInteger('player_job_id');
            $table->unsignedBigInteger('golden_eggs');
            $table->unsignedBigInteger('power_eggs');

            $table->primary(['salmon_id', 'player_id', 'player_job_id']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results');
            $table->foreign('player_id')->references('player_id')->on('users');
        });

        Schema::create('salmon_player_special_uses', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->unsignedTinyInteger('wave');
            $table->unsignedTinyInteger('count');

            $table->primary(['salmon_id', 'player_id', 'wave']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results');
            $table->foreign('player_id')->references('player_id')->on('users');
        });

        Schema::create('salmon_player_boss_eliminations', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->unsignedTinyInteger('wave');
            $table->unsignedTinyInteger('boss_id');
            $table->unsignedTinyInteger('count');

            $table->primary(['salmon_id', 'player_id', 'wave', 'boss_id'], 'salmon_player_boss_eliminations_pk');
            $table->foreign('salmon_id')->references('id')->on('salmon_results');
            $table->foreign('player_id')->references('player_id')->on('users');
        });

        Schema::create('salmon_player_weapons', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->unsignedTinyInteger('wave');
            $table->unsignedSmallInteger('weapon_id');

            $table->primary(['salmon_id', 'player_id', 'wave']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results');
            $table->foreign('player_id')->references('player_id')->on('users');
            $table->foreign('weapon_id')->references('id')->on('salmon_weapons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salmon_player_results');
        Schema::dropIfExists('salmon_player_special_uses');
        Schema::dropIfExists('salmon_player_boss_eliminations');
        Schema::dropIfExists('salmon_player_weapons');
    }
}
