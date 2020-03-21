<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PopulateUserAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $users = \App\User::all();

            foreach ($users as $user) {
                if (!isset($user->player_id)) {
                    continue;
                }

                $account = new \App\UserAccount();
                $account->user_id = $user->id;
                $account->player_id = $user->player_id;
                $account->is_primary = true;
                $account->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \App\UserAccount::query()->delete();
    }
}
