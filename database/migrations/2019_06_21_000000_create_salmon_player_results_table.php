<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalmonPlayerResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salmon_specials', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->statInkKey('key');
        });
        $specials = [
            [2, 'pitcher'],
            [7, 'presser'],
            [8, 'jetpack'],
            [9, 'chakuchi'],
        ];
        foreach ($specials as $special) {
            DB::table('salmon_specials')->insert([
                'id' => $special[0],
                'key' => $special[1],
            ]);
        }

        Schema::create('salmon_player_results', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->unsignedBigInteger('golden_eggs');
            $table->unsignedBigInteger('power_eggs');
            $table->unsignedTinyInteger('rescue');
            $table->unsignedTinyInteger('death');
            $table->unsignedTinyInteger('special_id');

            // This value can be calculated by joining / aggregating other tables,
            // however, for performance / simplicity sake, we need this column.
            $table->unsignedTinyInteger('boss_elimination_count');

            $table->primary(['salmon_id', 'player_id']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results')->onDelete('cascade');
            // $table->foreign('player_id')->references('player_id')->on('users')->onDelete('cascade');
            $table->foreign('special_id')->references('id')->on('salmon_specials')->onDelete('cascade');
        });

        Schema::create('salmon_player_special_uses', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->unsignedTinyInteger('wave');
            $table->unsignedTinyInteger('count');

            $table->primary(['salmon_id', 'player_id', 'wave']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results')->onDelete('cascade');
            // $table->foreign('player_id')->references('player_id')->on('users')->onDelete('cascade');
        });

        Schema::create('salmon_player_boss_eliminations', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->json('counts');

            $table->primary(['salmon_id', 'player_id'], 'salmon_player_boss_eliminations_pk');
            $table->foreign('salmon_id')->references('id')->on('salmon_results')->onDelete('cascade');
            // $table->foreign('player_id')->references('player_id')->on('users')->onDelete('cascade');
        });

        Schema::create('salmon_player_weapons', function (Blueprint $table) {
            $table->unsignedBigInteger('salmon_id');
            $table->playerId('player_id');
            $table->unsignedTinyInteger('wave');
            $table->smallInteger('weapon_id');

            $table->primary(['salmon_id', 'player_id', 'wave']);
            $table->foreign('salmon_id')->references('id')->on('salmon_results')->onDelete('cascade');
            // $table->foreign('player_id')->references('player_id')->on('users')->onDelete('cascade');
            $table->foreign('weapon_id')->references('id')->on('salmon_weapons')->onDelete('cascade');
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
        Schema::dropIfExists('salmon_specials');
        Schema::dropIfExists('salmon_player_boss_eliminations');
        Schema::dropIfExists('salmon_player_weapons');
    }
}
