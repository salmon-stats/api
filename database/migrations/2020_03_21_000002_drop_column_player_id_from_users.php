<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnPlayerIdFromUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('player_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->playerId('player_id')->unique()->nullable();
        });

        $accounts = \App\UserAccount::where('is_primary', true)->get();
        foreach ($accounts as $account) {
            $user = \App\User::where('id', $account->user_id)->first();
            $user->player_id = $account->player_id;
            $user->save();
        }
    }
}
